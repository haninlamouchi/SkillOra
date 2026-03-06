<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class GoogleAuthenticator extends OAuth2Authenticator
{
    public function __construct(
        private ClientRegistry $clientRegistry,
        private EntityManagerInterface $em,
        private RouterInterface $router
    ) {}

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'connect_google_check';
    }

    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('google');
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function () use ($accessToken, $client) {

                /** @var \League\OAuth2\Client\Provider\GoogleUser $googleUser */
                $googleUser = $client->fetchUserFromToken($accessToken);
                $email = $googleUser->getEmail() ?? '';

                // Cherche si l'utilisateur existe déjà
                $user = $this->em->getRepository(User::class)
                                 ->findOneBy(['email' => $email]);

                if (!$user) {
                    $user = new User();
                    $user->setEmail($email ?: 'google-user@example.com');

                    // Prénom — Google retourne parfois null
                    $firstName = $googleUser->getFirstName();
                    $user->setPrenom($firstName ?: 'User');

                    // Nom — Google retourne parfois null
                    $lastName = $googleUser->getLastName();
                    $user->setNom($lastName ?: 'Google');

                    // Pas de mot de passe pour OAuth
                    $user->setPassword('');

                    // Rôle par défaut
                    $user->setRole('etudiant');

                    // Téléphone par défaut (champ obligatoire dans ton entité)
                    $user->setTelephone('00000000');

                    // Date de naissance par défaut (champ obligatoire dans ton entité)
                    $user->setDateNaissance(new \DateTime('2000-01-01'));

                    // Photo Google → stockée comme URL externe
                    $avatar = $googleUser->getAvatar();
                    if ($avatar) {
                        $user->setPhoto($avatar);
                    }

                    // Date inscription déjà initialisée dans le constructeur
                    // UpdatedAt
                    $user->setUpdatedAt(new \DateTime());

                    $this->em->persist($user);
                    $this->em->flush();
                }

                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(
        Request $request,
        TokenInterface $token,
        string $firewallName
    ): ?Response {
        /** @var \App\Entity\User $user */
        $user = $token->getUser();

        switch ($user->getRole()) {
            case 'admin':
                return new RedirectResponse($this->router->generate('app_admin_dashboard'));
            
            case 'responsable_club':
                return new RedirectResponse($this->router->generate('app_responsable_dashboard_user'));
            
            case 'membre':
                return new RedirectResponse($this->router->generate('app_membre_dashboard'));
            
            case 'etudiant':
            default:
                return new RedirectResponse(
                    $this->router->generate('front_home_user', ['userId' => $user->getId()])
                );
        }
    }

    public function onAuthenticationFailure(
    Request $request,
    AuthenticationException $exception
): ?Response {
    // Symfony 6+ : utilise les flash messages via la session directement
    $session = $request->getSession();
    $session->set('_security.last_error', $exception->getMessage());

    return new RedirectResponse($this->router->generate('app_login'));
}
}