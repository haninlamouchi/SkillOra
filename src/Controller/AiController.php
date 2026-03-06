<?php

namespace App\Controller;

use App\Service\GeminiServiceForm;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AiController extends AbstractController
{
    #[Route('/ai', name: 'app_ai')]
    public function index(Request $request, GeminiServiceForm $gemini): Response
    {
        $result = null;

        if ($request->isMethod('POST')) {
            $prompt = $request->request->get('prompt');
            if (is_string($prompt)) {
                $result = $gemini->generateText($prompt);
            }
        }

        return $this->render('ai/index.html.twig', [
            'result' => $result
        ]);
    }
}