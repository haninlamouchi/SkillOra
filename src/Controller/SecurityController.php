<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use App\Service\FaceRecognitionService;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Si l'utilisateur est déjà connecté, rediriger
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        // Récupérer l'erreur de connexion s'il y en a une
        $error = $authenticationUtils->getLastAuthenticationError();
        
        // Dernier nom d'utilisateur saisi
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

        // ===== ROUTE 1 : Cherche l'utilisateur par email =====
    #[Route('/api/face-login', name: 'api_face_login', methods: ['POST'])]
    public function faceLogin(
        Request $request,
        UserRepository $userRepository
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;

        if (!$email) {
            return new JsonResponse(['success' => false, 'message' => 'Email required'], 400);
        }

        $user = $userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'User not found'], 404);
        }

        if (!$user->getPhoto()) {
            return new JsonResponse(['success' => false, 'message' => 'No profile photo found'], 404);
        }

        // Vérifie que c'est une photo locale (pas une URL Google)
        $photo = $user->getPhoto();
        if (str_starts_with($photo, 'http')) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Face login not available for Google accounts'
            ], 400);
        }

        return new JsonResponse([
            'success' => true,
            'userId'  => $user->getId(),
            'name'    => $user->getPrenom(),
        ]);
    }

    // ===== ROUTE 2 : Compare les visages =====
    #[Route('/api/face-compare', name: 'api_face_compare', methods: ['POST'])]
    public function faceCompare(
        Request $request,
        UserRepository $userRepository,
        FaceRecognitionService $faceService
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $userId = $data['userId'] ?? null;
        $capturedImage = $data['captured_image'] ?? null;

        if (!$userId || !$capturedImage) {
            return new JsonResponse(['success' => false, 'message' => 'Missing data'], 400);
        }

        $user = $userRepository->find($userId);
        if (!$user || !$user->getPhoto()) {
            return new JsonResponse(['success' => false, 'message' => 'User not found'], 404);
        }

                // Chemin absolu de la photo de profil
        /** @var string $projectDir */
        $projectDir = $this->getParameter('kernel.project_dir');
        $photoPath = str_replace('/', '\\', 
            $projectDir
            . '/public/uploads/photos/' 
            . $user->getPhoto()
        );

        // Appelle l'API Python
        $result = $faceService->compareFaces($capturedImage, $photoPath);

        if ($result['success'] && $result['match']) {
            // Génère token de session
            $token = bin2hex(random_bytes(32));
            $request->getSession()->set('face_auth_token', $token);
            $request->getSession()->set('face_auth_user_id', $userId);

            return new JsonResponse([
                'success'    => true,
                'match'      => true,
                'confidence' => $result['confidence'] ?? 0,
                'redirect'   => '/face-authenticate/' . $token,
            ]);
        }

        return new JsonResponse([
            'success' => true,
            'match'   => false,
            'message' => $result['message'] ?? 'Face not recognized',
        ]);
    }

    // ===== ROUTE 3 : Authentification finale =====
    #[Route('/face-authenticate/{token}', name: 'face_authenticate')]
    public function faceAuthenticate(
        string $token,
        UserRepository $userRepository,
        Request $request,
        TokenStorageInterface $tokenStorage
    ): Response {
        $sessionToken = $request->getSession()->get('face_auth_token');
        $userId = $request->getSession()->get('face_auth_user_id');

        if (!$sessionToken || $sessionToken !== $token || !$userId) {
            $this->addFlash('error', 'Face authentication failed.');
            return $this->redirectToRoute('app_login');
        }

        $user = $userRepository->find($userId);
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $request->getSession()->remove('face_auth_token');
        $request->getSession()->remove('face_auth_user_id');

        // ===== CONNEXION PROGRAMMATIQUE =====
        $authToken = new UsernamePasswordToken(
            $user,
            'main',  // nom du firewall
            $user->getRoles()
        );
        $tokenStorage->setToken($authToken);
        $request->getSession()->set('_security_main', serialize($authToken));
        // ====================================

        switch ($user->getRole()) {
            case 'admin':
                return $this->redirectToRoute('app_admin_dashboard');
            case 'responsable_club':
                return $this->redirectToRoute('app_responsable_dashboard_user');
            case 'membre':
                return $this->redirectToRoute('app_membre_dashboard');
            default:
                return $this->redirectToRoute('front_home_user', ['userId' => $user->getId()]);
        }
    }
    
}