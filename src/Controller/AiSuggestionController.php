<?php

namespace App\Controller;

use App\Entity\Commentaire;
use App\Entity\Publication;
use App\Service\AiSuggestionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class AiSuggestionController extends AbstractController
{
    // Pour les membres dans le forum
    #[Route('/ai/suggest/membre/{commentaireId}', name: 'app_ai_suggest_membre', requirements: ['commentaireId' => '\d+'], methods: ['POST'])]
    public function suggestMembre(
        int $commentaireId,
        EntityManagerInterface $em,
        AiSuggestionService $aiService
    ): JsonResponse {
        $commentaire = $em->getRepository(Commentaire::class)->find($commentaireId);
        if (!$commentaire) {
            return $this->json(['error' => 'Commentaire introuvable'], 404);
        }

        $publication = $commentaire->getPublication();
        if (!$publication) {
            return $this->json(['error' => 'Publication introuvable'], 404);
        }

        $suggestion = $aiService->suggererReponseMembre(
            $publication->getContenu() ?? '',
            $commentaire->getContenu() ?? ''
        );

        return $this->json(['suggestion' => $suggestion]);
    }

    // Pour les responsables dans la page supervision
    #[Route('/ai/suggest/responsable/{commentaireId}', name: 'app_ai_suggest_responsable', requirements: ['commentaireId' => '\d+'], methods: ['POST'])]
    public function suggestResponsable(
        int $commentaireId,
        EntityManagerInterface $em,
        AiSuggestionService $aiService
    ): JsonResponse {
        $commentaire = $em->getRepository(Commentaire::class)->find($commentaireId);
        if (!$commentaire) {
            return $this->json(['error' => 'Commentaire introuvable'], 404);
        }

        $publication = $commentaire->getPublication();
        if (!$publication) {
            return $this->json(['error' => 'Publication introuvable'], 404);
        }

        $suggestion = $aiService->suggererReponseResponsable(
            $publication->getContenu() ?? '',
            $commentaire->getContenu() ?? ''
        );

        return $this->json(['suggestion' => $suggestion]);
    }
}