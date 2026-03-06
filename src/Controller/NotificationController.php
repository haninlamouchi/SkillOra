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
    #[Route('/notifications/non-lues', name: 'app_notifications_non_lues', methods: ['GET'])]
    public function getNonLues(
        Request $request,
        NotificationService $notifService,
        UserRepository $userRepository
    ): JsonResponse {
        $userId = $request->query->get('u');
        $user = $userRepository->find((int) $userId);
        if (!$user) {
            return $this->json(['error' => 'Utilisateur introuvable'], 404);
        }

        $notifications = $notifService->getNonLues($user);

        $data = array_map(fn($n) => [
            'id'              => $n->getId(),
            'message'         => $n->getMessage(),
            'type'            => $n->getType(),
            'lienRedirection' => $n->getLienRedirection(),
            'createdAt'       => $n->getCreatedAt()->format('d/m/Y H:i'),
            'roleExpediteur'  => $n->getRoleExpediteur(),
        ], $notifications);

        return $this->json([
            'count'         => count($data),
            'notifications' => $data,
        ]);
    }

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