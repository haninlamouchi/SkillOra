<?php

namespace App\Controller;

use App\Entity\Challenge;
use App\Form\ChallengeType;
use App\Repository\ChallengeRepository;
use App\Repository\ParticipationRepository;
use App\Repository\LivrableChallengeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\LivrableChallenge;
use App\Entity\Groupe;
use App\Service\NotificationService;

#[Route('/responsable')]
final class ResponsableController extends AbstractController
{
    #[Route('/dashboard', name: 'app_responsable_dashboard')]
    public function dashboard(): Response
    {
        return $this->render('frontoffice/responsable/dashboard.html.twig');
    }

    #[Route('/challenges', name: 'app_responsable_challenges')]
public function listChallenges(Request $request, ChallengeRepository $challengeRepository): Response
{
    $search = $request->query->get('search', '');
    $sortBy = $request->query->get('sort', 'dateDebut');
    $order = $request->query->get('order', 'DESC');
    
    $queryBuilder = $challengeRepository->createQueryBuilder('c');
    
    // Recherche
    if ($search) {
        $queryBuilder->where('c.titre LIKE :search OR c.description LIKE :search')
            ->setParameter('search', '%' . $search . '%');
    }
    
    // Tri
    $validSorts = ['titre', 'dateDebut', 'dateFin'];
    if (in_array($sortBy, $validSorts)) {
        $queryBuilder->orderBy('c.' . $sortBy, $order);
    }
    
    $challenges = $queryBuilder->getQuery()->getResult();
    
    return $this->render('frontoffice/responsable/challenges/list.html.twig', [
        'challenges' => $challenges,
        'search' => $search,
        'sortBy' => $sortBy,
        'order' => $order,
    ]);
}

    #[Route('/challenges/nouveau', name: 'app_responsable_challenge_new', methods: ['GET', 'POST'])]
public function newChallenge(Request $request, EntityManagerInterface $entityManager, NotificationService $notificationService): Response
{
    $challenge = new Challenge();
    $form = $this->createForm(ChallengeType::class, $challenge);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        
        $imageFile = $form->get('image')->getData();
        if ($imageFile) {
            $newFilename = uniqid().'.'.$imageFile->guessExtension();
            try {
                $imageFile->move(
                    $this->getParameter('uploads_directory'),
                    $newFilename
                );
                $challenge->setImage($newFilename);
            } catch (FileException $e) {
                $this->addFlash('error', 'Erreur lors de l\'upload de l\'image.');
            }
        }

        $cahierFile = $form->get('fichierCahierCharges')->getData();
        if ($cahierFile) {
            $newFilename = uniqid().'.'.$cahierFile->guessExtension();
            try {
                $cahierFile->move(
                    $this->getParameter('uploads_directory'),
                    $newFilename
                );
                $challenge->setFichierCahierCharges($newFilename);
            } catch (FileException $e) {
                $this->addFlash('error', 'Erreur lors de l\'upload du fichier PDF.');
            }
        }

        $entityManager->persist($challenge);
        $entityManager->flush();

        // Envoyer les emails aux étudiants
$groupes = $entityManager->getRepository(Groupe::class)->findAll();

foreach ($groupes as $groupe) {
    foreach ($groupe->getMembres() as $membre) {
        $user = $membre->getUser();
        if ($user && $user->getEmail()) {
            $notificationService->envoyerEmailNouveauChallenge(
                $user->getEmail(),
                $user->getNom() . ' ' . $user->getPrenom(),
                $challenge->getTitre(),
                $challenge->getDateDebut()->format('d/m/Y'),
                $challenge->getDateFin()->format('d/m/Y')
            );
        }
    }
}

        $this->addFlash('success', 'Challenge créé avec succès ! 🎉 Les étudiants ont été notifiés par email.');
        return $this->redirectToRoute('app_responsable_challenges');
    }

    return $this->render('frontoffice/responsable/challenges/create.html.twig', [
        'form' => $form,
    ]);
}

    #[Route('/participations', name: 'app_responsable_participations')]
public function listParticipations(ParticipationRepository $participationRepository): Response
{
    $participations = $participationRepository->findAll();
    
    return $this->render('frontoffice/responsable/participations/list.html.twig', [
        'participations' => $participations,
    ]);
}

#[Route('/challenge/{id}/participations', name: 'app_responsable_challenge_participations', requirements: ['id' => '\d+'])]
public function challengeParticipations(Challenge $challenge): Response
{
    return $this->render('frontoffice/responsable/participations/by_challenge.html.twig', [
        'challenge' => $challenge,
        'participations' => $challenge->getParticipations(),
    ]);
}

#[Route('/livrables', name: 'app_responsable_livrables')]
public function listLivrables(Request $request, LivrableChallengeRepository $livrableRepository): Response
{
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
    
    $queryBuilder->orderBy('l.dateSoumission', 'DESC');
    
    $livrables = $queryBuilder->getQuery()->getResult();
    
    return $this->render('frontoffice/responsable/livrables/list.html.twig', [
        'livrables' => $livrables,
        'search' => $search,
        'filterStatut' => $filterStatut,
    ]);
}

#[Route('/livrable/{id}/valider', name: 'app_responsable_livrable_validate', methods: ['POST'])]
public function validateLivrable(Request $request, LivrableChallenge $livrable, EntityManagerInterface $entityManager): Response
{
    if ($this->isCsrfTokenValid('validate'.$livrable->getId(), $request->request->get('_token'))) {
        
        if ($livrable->getStatut() === 'valide') {
            $this->addFlash('error', 'Ce livrable est déjà validé ! ✅');
            return $this->redirectToRoute('app_responsable_livrables');
        }
    
        
        $livrable->setStatut('valide');
        $entityManager->flush(); 
        
    
        
        $this->addFlash('success', 'Livrable validé avec succès ! ✅');
    }
    
    return $this->redirectToRoute('app_responsable_livrables');
}

#[Route('/livrable/{id}/refuser', name: 'app_responsable_livrable_reject', methods: ['POST'])]
public function rejectLivrable(Request $request, LivrableChallenge $livrable, EntityManagerInterface $entityManager): Response
{
    if ($this->isCsrfTokenValid('reject'.$livrable->getId(), $request->request->get('_token'))) {
        if ($livrable->getStatut() === 'refuse') {
            $this->addFlash('error', 'Ce livrable est déjà refusé ! ❌');
            return $this->redirectToRoute('app_responsable_livrables');
        }
        
        $livrable->setStatut('refuse');
        $entityManager->flush();
        
        $this->addFlash('success', 'Livrable refusé ! ❌ Le groupe devra soumettre un nouveau livrable.');
    }
    
    return $this->redirectToRoute('app_responsable_livrables');
}

#[Route('/challenges/{id}', name: 'app_responsable_challenge_show', requirements: ['id' => '\d+'])]
public function showChallenge(Challenge $challenge): Response
{
    return $this->render('frontoffice/responsable/challenges/show.html.twig', [
        'challenge' => $challenge,
    ]);
}

#[Route('/challenges/{id}/modifier', name: 'app_responsable_challenge_edit', methods: ['GET', 'POST'])]
public function editChallenge(Request $request, Challenge $challenge, EntityManagerInterface $entityManager): Response
{
    $form = $this->createForm(ChallengeType::class, $challenge);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        // Gestion de l'image si changée
        $imageFile = $form->get('image')->getData();
        if ($imageFile) {
            $newFilename = uniqid().'.'.$imageFile->guessExtension();
            try {
                $imageFile->move(
                    $this->getParameter('uploads_directory'),
                    $newFilename
                );
                $challenge->setImage($newFilename);
            } catch (FileException $e) {
                $this->addFlash('error', 'Erreur lors de l\'upload de l\'image.');
            }
        }

        // Gestion du cahier des charges si changé
        $cahierFile = $form->get('fichierCahierCharges')->getData();
        if ($cahierFile) {
            $newFilename = uniqid().'.'.$cahierFile->guessExtension();
            try {
                $cahierFile->move(
                    $this->getParameter('uploads_directory'),
                    $newFilename
                );
                $challenge->setFichierCahierCharges($newFilename);
            } catch (FileException $e) {
                $this->addFlash('error', 'Erreur lors de l\'upload du fichier PDF.');
            }
        }

        $entityManager->flush();
        
        $this->addFlash('success', 'Challenge modifié avec succès ! ✅');
        return $this->redirectToRoute('app_responsable_challenges');
    }

    return $this->render('frontoffice/responsable/challenges/edit.html.twig', [
        'challenge' => $challenge,
        'form' => $form,
    ]);
}

#[Route('/challenges/{id}/supprimer', name: 'app_responsable_challenge_delete', methods: ['POST'])]
public function deleteChallenge(Request $request, Challenge $challenge, EntityManagerInterface $entityManager): Response
{
    if ($this->isCsrfTokenValid('delete'.$challenge->getId(), $request->request->get('_token'))) {
        
        // Vérifier si le challenge a des participations
        if ($challenge->getParticipations()->count() > 0) {
            $this->addFlash('error', 'Impossible de supprimer ce challenge : des groupes y participent. Supprimez d\'abord les participations.');
            return $this->redirectToRoute('app_responsable_challenges');
        }
        
        // Supprimer les fichiers uploadés
        if ($challenge->getImage()) {
            $imagePath = $this->getParameter('uploads_directory') . '/' . $challenge->getImage();
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }
        
        if ($challenge->getFichierCahierCharges()) {
            $pdfPath = $this->getParameter('uploads_directory') . '/' . $challenge->getFichierCahierCharges();
            if (file_exists($pdfPath)) {
                unlink($pdfPath);
            }
        }
        
        $entityManager->remove($challenge);
        $entityManager->flush();
        
        $this->addFlash('success', 'Challenge supprimé avec succès ! 🗑️');
    }
    
    return $this->redirectToRoute('app_responsable_challenges');
}
}