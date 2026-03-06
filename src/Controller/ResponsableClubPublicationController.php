<?php

namespace App\Controller;

use App\Entity\Publication;
use App\Enum\StatusPublication;
use App\Form\PublicationType;
use App\Repository\PublicationRepository;
use App\Repository\ClubRepository;
use App\Service\BrevoMailingService;           // ← IMPORT
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/responsable-club/publication')]
#[IsGranted('ROLE_RESPONSABLE_CLUB')]
class ResponsableClubPublicationController extends AbstractController
{
    private function getCurrentResponsable(): \App\Entity\User
    {
        $user = $this->getUser();
        if (!$user || !$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException('Vous devez être connecté.');
        }
        if ($user->getRole() !== 'responsable_club') {
            throw $this->createAccessDeniedException('Accès réservé aux responsables de club.');
        }
        return $user;
    }

    #[Route('/', name: 'app_responsable_club_publication_index')]
    public function index(
        PublicationRepository $repo,
        ClubRepository $clubRepo,
        Request $request,
        PaginatorInterface $paginator
    ): Response {
        $currentUser = $this->getCurrentResponsable();
        
        // Récupérer le club dont le responsable est responsable
        $club = $clubRepo->findOneBy(['responsable' => $currentUser]);
        
        // Si pas de club, afficher uniquement les publications du responsable
        if (!$club) {
            $memberIds = [$currentUser->getId()];
        } else {
            // Récupérer les IDs des membres du club
            $clubMembers = $club->getMembres();
            $memberIds = [];
            foreach ($clubMembers as $member) {
                $memberIds[] = $member->getId();
            }
            // Ajouter le responsable lui-même à la liste
            $memberIds[] = $currentUser->getId();
        }

        // Filtrer les publications par les membres du club (ou juste le responsable)
        $queryBuilder = $repo->createQueryBuilder('p')
            ->leftJoin('p.user', 'u')
            ->addSelect('u')
            ->where('u.id IN (:memberIds)')
            ->setParameter('memberIds', $memberIds)
            ->orderBy('p.datePublication', 'DESC');

        $publications = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            6 // publications par page
        );

        return $this->render('publication/responsable_club_publication_index.html.twig', [
            'publications' => $publications,
            'currentUser'  => $currentUser,
            'club'         => $club,
        ]);
    }

    #[Route('/show/{id}', name: 'app_responsable_club_publication_show')]
    public function show(Publication $publication, ClubRepository $clubRepo): Response
    {
        $currentUser = $this->getCurrentResponsable();
        
        // Vérifier que l'auteur est membre du club du responsable
        $club = $clubRepo->findOneBy(['responsable' => $currentUser]);
        if ($club) {
            $author = $publication->getUser();
            if ($author && !$club->getMembres()->contains($author) && $author->getId() !== $currentUser->getId()) {
                $this->addFlash('danger', 'Cette publication n\'appartient pas à un membre de votre club.');
                return $this->redirectToRoute('app_responsable_club_publication_index');
            }
        }
        return $this->render('publication/Responsable club publication show.html.twig', [
            'publication' => $publication,
            'currentUser' => $currentUser,
        ]);
    }

    // ══════════════════════════════════════════════════════════
    //  NEW — Responsable publie directement → PUBLIÉ → email admin
    // ══════════════════════════════════════════════════════════
    #[Route('/new', name: 'app_responsable_club_publication_new')]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        BrevoMailingService $brevoMailingService  // ← INJECTION
    ): Response {
        $user = $this->getCurrentResponsable();

        $publication = new Publication();
        $publication->setUser($user);
        $publication->setStatus(StatusPublication::PUBLIE);

        $form = $this->createForm(PublicationType::class, $publication, ['show_status' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $publication->setStatus(StatusPublication::PUBLIE);
            
            $em->persist($publication);
            $em->flush();

            // ✅ Email admin — responsable publie directement sur le forum
            try {
                $brevoMailingService->notifierNouvellePublicationResponsable($publication);
            } catch (\Exception $e) {
                error_log('[Brevo] Erreur envoi email : ' . $e->getMessage());
            }

            return $this->redirectToRoute('app_responsable_club_publication_index');
        }

        return $this->render('publication/Responsable club publication new.html.twig', [
            'form'        => $form->createView(),
            'currentUser' => $user,
        ]);
    }

    #[Route('/edit/{id}', name: 'app_responsable_club_publication_edit')]
    public function edit(Publication $publication, Request $request, EntityManagerInterface $em, ClubRepository $clubRepo): Response
    {
        $currentUser = $this->getCurrentResponsable();
        
        // Vérifier que l'auteur est membre du club du responsable
        $club = $clubRepo->findOneBy(['responsable' => $currentUser]);
        if ($club) {
            $author = $publication->getUser();
            if ($author && !$club->getMembres()->contains($author) && $author->getId() !== $currentUser->getId()) {
                $this->addFlash('danger', 'Vous ne pouvez modifier que les publications des membres de votre club.');
                return $this->redirectToRoute('app_responsable_club_publication_index');
            }
        }
        $form = $this->createForm(PublicationType::class, $publication, ['show_status' => true]);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            return $this->redirectToRoute('app_responsable_club_publication_index');
        }
        
        return $this->render('publication/Responsable club publication edit.html.twig', [
            'publication'  => $publication,
            'form'         => $form->createView(),
            'currentUser'  => $currentUser,
        ]);
    }

    #[Route('/delete/{id}', name: 'app_responsable_club_publication_delete')]
    public function delete(Publication $publication, EntityManagerInterface $em, ClubRepository $clubRepo): Response
    {
        $currentUser = $this->getCurrentResponsable();
        
        // Vérifier que l'auteur est membre du club du responsable
        $club = $clubRepo->findOneBy(['responsable' => $currentUser]);
        if ($club) {
            $author = $publication->getUser();
            if ($author && !$club->getMembres()->contains($author) && $author->getId() !== $currentUser->getId()) {
                $this->addFlash('danger', 'Vous ne pouvez supprimer que les publications des membres de votre club.');
                return $this->redirectToRoute('app_responsable_club_publication_index');
            }
        }
        $em->remove($publication);
        $em->flush();
        return $this->redirectToRoute('app_responsable_club_publication_index');
    }

    // ══════════════════════════════════════════════════════════
    //  VALIDER — Responsable valide publication étudiant → PUBLIÉ → email admin
    // ══════════════════════════════════════════════════════════
    #[Route('/valider/{id}', name: 'app_responsable_club_publication_valider')]
    public function valider(
        Publication $publication,
        EntityManagerInterface $em,
        ClubRepository $clubRepo,
        NotificationService $notificationService,
        BrevoMailingService $brevoMailingService  // ← INJECTION
    ): Response {
        $currentUser = $this->getCurrentResponsable();
        
        // Vérifier que le responsable est bien responsable d'un club
        $club = $clubRepo->findOneBy(['responsable' => $currentUser]);
        
        if (!$club) {
            $this->addFlash('danger', 'Vous devez être responsable d\'un club.');
            return $this->redirectToRoute('app_responsable_club_publication_index');
        }
        
        // Vérifier que l'auteur de la publication est membre du club
        $author = $publication->getUser();
        if ($author && !$club->getMembres()->contains($author) && $author->getId() !== $currentUser->getId()) {
            $this->addFlash('danger', 'Vous ne pouvez valider que les publications des membres de votre club.');
            return $this->redirectToRoute('app_responsable_club_publication_index');
        }

        // Changer le statut → PUBLIÉ
        $publication->setStatus(StatusPublication::PUBLIE);
        $em->flush();

        // 🔔 Notification Mercure → notifier l'étudiant auteur
        $etudiant = $publication->getUser();
        if ($etudiant && $etudiant->getId() !== $currentUser->getId()) {
            try {
                $notificationService->notifierValidation($currentUser, $etudiant, $publication);
            } catch (\Exception $e) {
                error_log('[Mercure] Erreur notification : ' . $e->getMessage());
            }
        }

        // 📧 Email admin — publication étudiant maintenant PUBLIÉ sur le forum
        try {
            $brevoMailingService->notifierNouvellePublicationEtudiant($publication);
        } catch (\Exception $e) {
            error_log('[Brevo] Erreur envoi email : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_responsable_club_publication_index');
    }
}