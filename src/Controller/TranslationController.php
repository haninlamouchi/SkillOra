<?php

namespace App\Controller;

use App\Service\TranslationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class TranslationController extends AbstractController
{
    #[Route('/translate', name: 'app_translate', methods: ['POST'])]
    public function translate(
        Request $request,
        TranslationService $translationService
    ): JsonResponse {
        $data   = json_decode($request->getContent(), true);
        $texte  = $data['text'] ?? '';
        $langue = $data['lang'] ?? 'en';

        if (!$texte) {
            return $this->json(['error' => 'Texte manquant'], 400);
        }

        $traduit = $translationService->traduire($texte, $langue);
        return $this->json(['translated' => $traduit]);
    }
}