<?php

namespace App\Controller;

use App\Service\PublicationAIService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/publication-ai')]
class PublicationAIController extends AbstractController
{
    public function __construct(
        private PublicationAIService $aiService,
        private EntityManagerInterface $entityManager,
    ) {}

    /**
     * Generate BOTH title and description from keywords
     * POST /api/publication-ai/generate-full
     */
    #[Route('/generate-full', name: 'app_ai_generate_full', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function generateFull(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            $keywords = trim($data['keywords'] ?? '');
            $tone = $data['tone'] ?? 'friendly';
            $maxLength = (int)($data['maxLength'] ?? 500);

            if (empty($keywords)) {
                return $this->json([
                    'success' => false,
                    'error' => 'Les mots-clés sont requis',
                ], 400);
            }

            if (!$this->aiService->isAvailable()) {
                return $this->json([
                    'success' => false,
                    'error' => 'Service IA non disponible',
                ], 503);
            }

            $result = $this->aiService->generateTitleAndDescription(
                $keywords,
                $tone,
                $maxLength
            );

            if (!$result['success']) {
                return $this->json($result, 400);
            }

            return $this->json([
                'success' => true,
                'title' => $result['title'],
                'description' => $result['description'],
            ]);

        } catch (\Throwable $e) {
            return $this->json([
                'success' => false,
                'error' => 'Erreur serveur: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate publication description from keywords
     * POST /api/publication-ai/generate-description
     */
    #[Route('/generate-description', name: 'app_ai_generate_description', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function generateDescription(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            $keywords = trim($data['keywords'] ?? '');
            $title = trim($data['title'] ?? '') ?: null;
            $tone = $data['tone'] ?? 'friendly';
            $maxLength = (int)($data['maxLength'] ?? 500);

            if (empty($keywords)) {
                return $this->json([
                    'success' => false,
                    'error' => 'Les mots-clés sont requis',
                ], 400);
            }

            if (!$this->aiService->isAvailable()) {
                return $this->json([
                    'success' => false,
                    'error' => 'Service IA non disponible',
                ], 503);
            }

            $result = $this->aiService->generateDescription(
                $keywords,
                $title,
                $tone,
                $maxLength
            );

            if (!$result['success']) {
                return $this->json($result, 400);
            }

            return $this->json([
                'success' => true,
                'description' => $result['description'],
            ]);

        } catch (\Throwable $e) {
            return $this->json([
                'success' => false,
                'error' => 'Erreur serveur: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Enhance/improve existing description
     * POST /api/publication-ai/enhance-description
     */
    #[Route('/enhance-description', name: 'app_ai_enhance_description', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function enhanceDescription(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            $description = trim($data['description'] ?? '');
            $keywords = trim($data['keywords'] ?? '');
            $tone = $data['tone'] ?? 'friendly';

            if (empty($description) || empty($keywords)) {
                return $this->json([
                    'success' => false,
                    'error' => 'La description et les mots-clés sont requis',
                ], 400);
            }

            if (!$this->aiService->isAvailable()) {
                return $this->json([
                    'success' => false,
                    'error' => 'Service IA non disponible',
                ], 503);
            }

            $result = $this->aiService->enhanceDescription(
                $description,
                $keywords,
                $tone
            );

            if (!$result['success']) {
                return $this->json($result, 400);
            }

            return $this->json([
                'success' => true,
                'description' => $result['description'],
            ]);

        } catch (\Throwable $e) {
            return $this->json([
                'success' => false,
                'error' => 'Erreur serveur',
            ], 500);
        }
    }

    /**
     * Get writing suggestions based on keywords
     * POST /api/publication-ai/suggestions
     */
    #[Route('/suggestions', name: 'app_ai_suggestions', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function suggestions(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $keywords = trim($data['keywords'] ?? '');

            if (empty($keywords)) {
                return $this->json([
                    'success' => false,
                    'error' => 'Les mots-clés sont requis',
                ], 400);
            }

            if (!$this->aiService->isAvailable()) {
                return $this->json([
                    'success' => false,
                    'error' => 'Service IA non disponible',
                ], 503);
            }

            $result = $this->aiService->generateWritingSuggestions($keywords);

            if (!$result['success']) {
                return $this->json($result, 400);
            }

            return $this->json([
                'success' => true,
                'suggestions' => $result['suggestions'],
            ]);

        } catch (\Throwable $e) {
            return $this->json([
                'success' => false,
                'error' => 'Erreur serveur',
            ], 500);
        }
    }

    /**
     * Create publication from AI-generated content
     * POST /api/publication-ai/publish
     */
    #[Route('/publish', name: 'app_ai_publish', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function publish(Request $request): JsonResponse
    {
        try {
            // Get current user
            $user = $this->getUser();
            if (!$user instanceof \App\Entity\User) {
                return $this->json([
                    'success' => false,
                    'error' => 'Utilisateur non authentifié',
                ], 401);
            }

            // Vérifier que c'est un membre, étudiant ou responsable club
            if (!in_array($user->getRole(), ['membre', 'etudiant', 'responsable_club'])) {
                return $this->json([
                    'success' => false,
                    'error' => 'Accès réservé aux membres',
                ], 403);
            }

            // Get POST data
            $title = trim((string) $request->request->get('title', ''));
            $content = trim((string) $request->request->get('content', ''));
            $type = $request->request->get('type', 'texte');

            // Validate
            if (empty($title) || empty($content)) {
                return $this->json([
                    'success' => false,
                    'error' => 'Le titre et le contenu sont obligatoires',
                ], 400);
            }

            // Create publication
            $publication = new \App\Entity\Publication();
            $publication->setUser($user);
            $publication->setTitre($title);
            $publication->setContenu($content);
            
            // Responsables de club publient directement, autres membres en attente de modération
            if ($user->getRole() === 'responsable_club') {
                $publication->setStatus(\App\Enum\StatusPublication::PUBLIE);
            } else {
                $publication->setStatus(\App\Enum\StatusPublication::EN_ATTENTE);
            }

            // Set type based on selection
            switch ($type) {
                case 'image':
                    $publication->setTypeContenu(\App\Enum\TypeContenu::IMAGE);
                    break;
                case 'video':
                    $publication->setTypeContenu(\App\Enum\TypeContenu::VIDEO);
                    break;
                default:
                    $publication->setTypeContenu(\App\Enum\TypeContenu::TEXTE);
                    break;
            }

            // Handle media file upload if present
            $mediaFile = $request->files->get('media');
            if ($mediaFile) {
                // Validate file type based on content type
                $mimeType = $mediaFile->getMimeType();
                $isImage = str_starts_with($mimeType, 'image/');
                $isVideo = str_starts_with($mimeType, 'video/');
                
                if ($type === 'image' && !$isImage) {
                    return $this->json([
                        'success' => false,
                        'error' => 'Le fichier doit être une image (JPG, PNG, GIF, etc.)',
                    ], 400);
                }
                
                if ($type === 'video' && !$isVideo) {
                    return $this->json([
                        'success' => false,
                        'error' => 'Le fichier doit être une vidéo (MP4, WEBM, MOV, etc.)',
                    ], 400);
                }
                
                // VichUploader will handle the upload automatically
                $publication->setImageFile($mediaFile);
            }

            // Persist to database
            $this->entityManager->persist($publication);
            $this->entityManager->flush();

            $response = [
                'success' => true,
                'id' => $publication->getId(),
                'message' => 'Publication créée avec succès ! Elle sera visible après validation.',
                'type' => $type,
            ];
            
            // Include filename if a file was uploaded
            if ($publication->getFichier()) {
                $response['fichier'] = $publication->getFichier();
                $response['message'] .= ' (Fichier : ' . $publication->getFichier() . ')';
            }

            return $this->json($response);

        } catch (\Throwable $e) {
            return $this->json([
                'success' => false,
                'error' => 'Erreur lors de la création de la publication: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check if AI service is available
     * GET /api/publication-ai/status
     */
    #[Route('/status', name: 'app_ai_status', methods: ['GET'])]
    public function status(): JsonResponse
    {
        return $this->json([
            'available' => $this->aiService->isAvailable(),
        ]);
    }
}
