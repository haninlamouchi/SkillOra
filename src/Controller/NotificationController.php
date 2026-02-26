<?php

namespace App\Controller;

use App\Service\NotificationService;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class NotificationController extends AbstractController
{
    #[Route('/notifications/lire', name: 'app_notifications_lire', methods: ['POST'])]
    public function marquerLu(
        Request $request,
        NotificationService $notifService,
        UserRepository $userRepository
    ): JsonResponse {
        $userId = $request->query->get('u');
        $user = $userRepository->find((int) $userId);
        if (!$user) {
            return $this->json(['error' => 'Utilisateur introuvable'], 404);
        }
        $notifService->marquerToutLu((int) $userId);
        return $this->json(['success' => true]);
    }
}