<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Repository\ClubRepository;
use App\Repository\FormationRepository;
use App\Repository\ChallengeRepository;
use App\Repository\PublicationRepository;
use App\Repository\CommentaireRepository;
use App\Repository\LivrableChallengeRepository;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\LivrableChallenge;




#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('/dashboard', name: 'app_admin_dashboard')]
    public function dashboard(
        UserRepository       $userRepo,
        ClubRepository       $clubRepo,
        FormationRepository  $formationRepo,
        ChallengeRepository  $challengeRepo,
        PublicationRepository $publicationRepo,
        CommentaireRepository $commentaireRepo,
        NotificationRepository $notifRepo
    ): Response {
        // ── Compteurs ──────────────────────────────────────
        $totalUsers       = $userRepo->count([]);
        $totalClubs       = $clubRepo->count([]);
        $totalFormations  = $formationRepo->count([]);
        $totalChallenges  = $challengeRepo->count([]);
        $totalForums      = $publicationRepo->count([]);
        $totalCommentaires = $commentaireRepo->count([]);

        // ── Publications en attente de validation ──────────
        $publicationsEnAttente = $publicationRepo->findBy(
            ['status' => 'EN_ATTENTE'],
            ['datePublication' => 'DESC'],
            5
        );

        // ── Derniers utilisateurs inscrits ─────────────────
        $dernierUtilisateurs = $userRepo->findBy(
            [],
            ['id' => 'DESC'],
            3
        );

        // ── Activité mensuelle (publications par mois) ─────
        $publicationsParMois = [];
        $inscriptionsParMois = [];
        $annee = (int) date('Y');

        for ($m = 1; $m <= 12; $m++) {
            $debut = new \DateTime("$annee-$m-01");
            $fin   = (clone $debut)->modify('last day of this month')->setTime(23, 59, 59);

            $publicationsParMois[] = $publicationRepo->createQueryBuilder('p')
                ->select('COUNT(p.id)')
                ->where('p.datePublication BETWEEN :debut AND :fin')
                ->setParameter('debut', $debut)
                ->setParameter('fin', $fin)
                ->getQuery()
                ->getSingleScalarResult();

            $inscriptionsParMois[] = $userRepo->createQueryBuilder('u')
                ->select('COUNT(u.id)')
                ->where('u.dateInscription BETWEEN :debut AND :fin')
                ->setParameter('debut', $debut)
                ->setParameter('fin', $fin)
                ->getQuery()
                ->getSingleScalarResult();
        }

        return $this->render('backoffice/dashboard.html.twig', [
            'user'                  => $this->getUser(),
            'totalUsers'            => $totalUsers,
            'totalClubs'            => $totalClubs,
            'totalFormations'       => $totalFormations,
            'totalChallenges'       => $totalChallenges,
            'totalForums'           => $totalForums,
            'totalCommentaires'     => $totalCommentaires,
            'publicationsEnAttente' => $publicationsEnAttente,
            'dernierUtilisateurs'   => $dernierUtilisateurs,
            'publicationsParMois'   => $publicationsParMois,
            'inscriptionsParMois'   => $inscriptionsParMois,          
            'adminNotifications'      => $notifRepo->findUnreadForAdmin(),  // ← AJOUTE
            'adminNotificationsCount' => $notifRepo->countUnreadForAdmin(), // ← AJOUTE

        ]);
    }

    #[Route('/users/role/{role}', name: 'app_admin_users_by_role')]
    public function usersByRole(string $role, UserRepository $userRepository,  NotificationRepository $notifRepo ): Response
    {
        $users = $userRepository->findBy(['role' => $role]);

        return $this->render('backoffice/user/users_by_role.html.twig', [
            'users' => $users,
            'role'  => $role,
            'adminNotifications'      => $notifRepo->findUnreadForAdmin(),
            'adminNotificationsCount' => $notifRepo->countUnreadForAdmin(),
        ]);
    }
    #[Route('/notifications/read', name: 'app_notifications_mark_read', methods: ['POST'])]
public function markNotificationsRead(
    NotificationRepository $notifRepo,
    EntityManagerInterface $em
): Response {
    foreach ($notifRepo->findUnreadForAdmin() as $notif) {
        $notif->setIsLu(true);
    }
    $em->flush();
    return $this->json(['success' => true]);
}

    #[Route('/notifications/admin', name: 'app_notifications_admin_list', methods: ['GET'])]
public function notificationsAdminList(
    NotificationRepository $notifRepo
): JsonResponse {
    $notifs = $notifRepo->findUnreadForAdmin();
    $count  = $notifRepo->countUnreadForAdmin();

    $data = array_map(fn($n) => [
        'id'              => $n->getId(),
        'message'         => $n->getMessage(),
        'type'            => $n->getType(),
        'lienRedirection' => $n->getLienRedirection() ?? '#',
        'createdAt'       => $n->getCreatedAt()->format('d/m/Y H:i'),
    ], $notifs);

    return $this->json([
        'count'         => $count,
        'notifications' => $data,
    ]);
}

    #[Route('/notifications/admin/{id}/read', name: 'app_notifications_admin_read', methods: ['POST'])]
public function markNotificationRead(
    int $id,
    NotificationRepository $notifRepo,
    EntityManagerInterface $em
): JsonResponse {
    $notif = $notifRepo->find($id);
    if ($notif) {
        $notif->setIsLu(true);
        $em->flush();
    }
    return $this->json(['success' => true]);
}
#[Route('/evaluations', name: 'app_admin_evaluations')]
public function listEvaluations(
    Request $request, 
    LivrableChallengeRepository $livrableRepository, 
    PaginatorInterface $paginator
): Response {
    $search = $request->query->get('search', '');
    $filterStatut = $request->query->get('statut', '');
    
    $queryBuilder = $livrableRepository->createQueryBuilder('l')
        ->leftJoin('l.challenge', 'c')
        ->leftJoin('l.groupe', 'g');
    
    if ($search) {
        $queryBuilder->where('c.titre LIKE :search OR g.nomGroupe LIKE :search')
            ->setParameter('search', '%' . $search . '%');
    }
    
    if ($filterStatut) {
        $queryBuilder->andWhere('l.statut = :statut')
            ->setParameter('statut', $filterStatut);
    }
    
    $queryBuilder->orderBy('l.dateEvaluation', 'DESC');

    $livrables = $paginator->paginate(
        $queryBuilder,
        $request->query->getInt('page', 1),
        10
    );
    
    return $this->render('backoffice/evaluations/list.html.twig', [
        'livrables' => $livrables,
        'search' => $search,
        'filterStatut' => $filterStatut,
    ]);
}

#[Route('/evaluation/{id}', name: 'app_admin_evaluation_detail')]
public function voirEvaluation(LivrableChallenge $livrable): Response
{
    return $this->render('backoffice/evaluations/detail.html.twig', [
        'livrable' => $livrable,
    ]);
}
}