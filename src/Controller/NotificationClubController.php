<?php

namespace App\Controller;

use App\Repository\NotificationClubRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/notifications/club')]
class NotificationClubController extends AbstractController
{
    // Récupérer les notifications non lues
    #[Route('/', name: 'notification_club_index', methods: ['GET'])]
    public function index(NotificationClubRepository $repo): JsonResponse
    {
        $user = $this->getUser();

        $notifications = $repo->findBy([
            'destinataire' => $user,
            'isRead'       => false,
        ], ['createdAt' => 'DESC']);

        $data = array_map(fn($n) => [
            'id'              => $n->getId(),
            'message'         => $n->getMessage(),
            'lienRedirection' => $n->getLienRedirection(),
            'createdAt'       => $n->getCreatedAt()?->format('d/m/Y H:i') ?? '',
        ], $notifications);

        return new JsonResponse([
            'count'         => count($data),
            'notifications' => $data,
        ]);
    }

    // Marquer une notification comme lue
    #[Route('/{id}/read', name: 'notification_club_read', methods: ['POST'])]
    public function markAsRead(
        int $id,
        NotificationClubRepository $repo,
        EntityManagerInterface $em
    ): JsonResponse {
        $notification = $repo->find($id);

        if ($notification && $notification->getDestinataire() === $this->getUser()) {
            $notification->setIsRead(true);
            $em->flush();
        }

        return new JsonResponse(['success' => true]);
    }
}