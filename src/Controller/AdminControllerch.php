<?php

namespace App\Controller;

use App\Entity\Challenge;
use App\Entity\Participation;
use App\Entity\LivrableChallenge;
use App\Entity\Groupe;
use App\Entity\User;
use App\Repository\ChallengeRepository;
use App\Repository\ParticipationRepository;
use App\Repository\LivrableChallengeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminControllerch extends AbstractController
{
    #[Route('/admin/dashboard', name: 'admin_dashboard')]
    public function dashboard(EntityManagerInterface $em): Response
    {
        // Statistiques
        $totalChallenges = $em->getRepository(Challenge::class)->count([]);
        $totalParticipations = $em->getRepository(Participation::class)->count([]);
        $totalLivrables = $em->getRepository(LivrableChallenge::class)->count([]);
        $totalGroupes = $em->getRepository(Groupe::class)->count([]);
        $totalUsers = $em->getRepository(User::class)->count([]);
        
        // Livrables par statut
        $livrablesEnAttente = $em->getRepository(LivrableChallenge::class)->count(['statut' => 'en_attente']);
        $livrablesValides = $em->getRepository(LivrableChallenge::class)->count(['statut' => 'valide']);
        $livrablesRefuses = $em->getRepository(LivrableChallenge::class)->count(['statut' => 'refuse']);
        
        return $this->render('backoffice/dashboard.html.twig', [
            'totalChallenges' => $totalChallenges,
            'totalParticipations' => $totalParticipations,
            'totalLivrables' => $totalLivrables,
            'totalGroupes' => $totalGroupes,
            'totalUsers' => $totalUsers,
            'livrablesEnAttente' => $livrablesEnAttente,
            'livrablesValides' => $livrablesValides,
            'livrablesRefuses' => $livrablesRefuses,
        ]);
    }

    #[Route('/admin/challenges', name: 'admin_challenges')]
    public function challenges(Request $request, ChallengeRepository $repo): Response
    {
        $search = $request->query->get('search', '');
        
        $qb = $repo->createQueryBuilder('c');
        if ($search) {
            $qb->where('c.titre LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }
        $qb->orderBy('c.dateDebut', 'DESC');
        
        $challenges = $qb->getQuery()->getResult();
        
        return $this->render('backoffice/challenges/list.html.twig', [
            'challenges' => $challenges,
            'search' => $search,
        ]);
    }

    #[Route('/admin/participations', name: 'admin_participations')]
    public function participations(Request $request, ParticipationRepository $repo): Response
    {
        $search = $request->query->get('search', '');
        
        $qb = $repo->createQueryBuilder('p')
            ->leftJoin('p.challenge', 'c')
            ->leftJoin('p.groupe', 'g');
        
        if ($search) {
            $qb->where('c.titre LIKE :search OR g.nomGroupe LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }
        
        $qb->orderBy('p.dateParticipation', 'DESC');
        
        $participations = $qb->getQuery()->getResult();
        
        return $this->render('backoffice/participations/list.html.twig', [
            'participations' => $participations,
            'search' => $search,
        ]);
    }

    #[Route('/admin/livrables', name: 'admin_livrables')]
    public function livrables(Request $request, LivrableChallengeRepository $repo): Response
    {
        $search = $request->query->get('search', '');
        $statut = $request->query->get('statut', '');
        
        $qb = $repo->createQueryBuilder('l')
            ->leftJoin('l.challenge', 'c')
            ->leftJoin('l.groupe', 'g');
        
        if ($search) {
            $qb->where('c.titre LIKE :search OR g.nomGroupe LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }
        
        if ($statut) {
            $qb->andWhere('l.statut = :statut')
                ->setParameter('statut', $statut);
        }
        
        $qb->orderBy('l.dateSoumission', 'DESC');
        
        $livrables = $qb->getQuery()->getResult();
        
        return $this->render('backoffice/livrables/list.html.twig', [
            'livrables' => $livrables,
            'search' => $search,
            'statut' => $statut,
        ]);
    }
}