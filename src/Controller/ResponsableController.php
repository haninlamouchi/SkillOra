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
use App\Service\NotificationServicech;
use Knp\Component\Pager\PaginatorInterface;
use App\Form\ChallengeAIType;
use App\Service\GroqService;
use App\Service\PdfGeneratorService;
use App\Service\NotificationChallengeService;
use App\Form\EvaluationLivrableType;
use App\Entity\EvaluationMembre;

#[Route('/responsable')]
final class ResponsableController extends AbstractController
{
    private function checkRole(): ?Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        if (!$user instanceof \App\Entity\User || $user->getRole() !== 'responsable_club') {
            throw $this->createAccessDeniedException('Accès réservé aux responsables de club.');
        }
        return null;
    }
    #[Route('/dashboard', name: 'app_responsable_dashboard_ch')]
    public function dashboard(): Response
    {
        if ($redirect = $this->checkRole()) return $redirect;
        return $this->render('frontoffice/responsable/dashboard.html.twig');
    }

    #[Route('/challenges', name: 'app_responsable_challenges')]
public function listChallenges(Request $request, ChallengeRepository $challengeRepository, PaginatorInterface $paginator): Response
{
        if ($redirect = $this->checkRole()) return $redirect;
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

    // PAGINATION - 10 challenges par page
    $challenges = $paginator->paginate(
        $queryBuilder,
        $request->query->getInt('page', 1),
        5
    );
    
    return $this->render('frontoffice/responsable/challenges/list.html.twig', [
        'challenges' => $challenges,
        'search' => $search,
        'sortBy' => $sortBy,
        'order' => $order,
    ]);
}

    #[Route('/challenges/nouveau', name: 'app_responsable_challenge_new', methods: ['GET', 'POST'])]
public function newChallenge(Request $request, EntityManagerInterface $entityManager, NotificationServicech $notificationService): Response
{
        if ($redirect = $this->checkRole()) return $redirect;
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
        $dateDebut = $challenge->getDateDebut();
        $dateFin = $challenge->getDateFin();
        if ($user && $user->getEmail() && $dateDebut && $dateFin) {
            $notificationService->envoyerEmailNouveauChallenge(
                $user->getEmail(),
                $user->getNom() . ' ' . $user->getPrenom(),
                $challenge->getTitre() ?? '',
                $dateDebut->format('d/m/Y'),
                $dateFin->format('d/m/Y')
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
public function listParticipations(Request $request, ParticipationRepository $participationRepository, PaginatorInterface $paginator): Response
{
        if ($redirect = $this->checkRole()) return $redirect;
    $queryBuilder = $participationRepository->createQueryBuilder('p')
        ->orderBy('p.dateParticipation', 'DESC');
    
    $participations = $paginator->paginate(
        $queryBuilder,
        $request->query->getInt('page', 1),
        5
    );
    
    return $this->render('frontoffice/responsable/participations/list.html.twig', [
        'participations' => $participations,
    ]);
}

#[Route('/challenge/{id}/participations', name: 'app_responsable_challenge_participations', requirements: ['id' => '\d+'])]
public function challengeParticipations(Challenge $challenge): Response
{
        if ($redirect = $this->checkRole()) return $redirect;
    return $this->render('frontoffice/responsable/participations/by_challenge.html.twig', [
        'challenge' => $challenge,
        'participations' => $challenge->getParticipations(),
    ]);
}

#[Route('/livrables', name: 'app_responsable_livrables')]
public function listLivrables(Request $request, LivrableChallengeRepository $livrableRepository, PaginatorInterface $paginator): Response
{
        if ($redirect = $this->checkRole()) return $redirect;
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

    $livrables = $paginator->paginate(
        $queryBuilder,
        $request->query->getInt('page', 1),
        3
    );
    
    return $this->render('frontoffice/responsable/livrables/list.html.twig', [
        'livrables' => $livrables,
        'search' => $search,
        'filterStatut' => $filterStatut,
    ]);
}

#[Route('/livrable/{id}/valider', name: 'app_responsable_livrable_validate', methods: ['POST'])]
public function validateLivrable(Request $request, LivrableChallenge $livrable, EntityManagerInterface $entityManager, NotificationChallengeService $notifService): Response
{
        if ($redirect = $this->checkRole()) return $redirect;
    if ($this->isCsrfTokenValid('validate'.$livrable->getId(), is_string($token = $request->request->get('_token')) ? $token : null)) {
        
        if ($livrable->getStatut() === 'valide') {
            $this->addFlash('error', 'Ce livrable est déjà validé ! ✅');
            return $this->redirectToRoute('app_responsable_livrables');
        }
    
        
        $livrable->setStatut('valide');
        $entityManager->flush(); 
        // Notifier les membres du groupe
        $groupe = $livrable->getGroupe();
        if ($groupe) {
            foreach ($groupe->getMembres() as $membre) {
                $user = $membre->getUser();
                $currentUser = $this->getUser();
                if ($user && $currentUser instanceof \App\Entity\User && in_array($user->getRole(), ['membre', 'etudiant'])) {
                    $notifService->notifierLivrableValide($currentUser, $user, $livrable);
                }
            }
        }
        
    
        
        $this->addFlash('success', 'Livrable validé avec succès ! ✅');
    }
    
    return $this->redirectToRoute('app_responsable_livrables');
}

#[Route('/livrable/{id}/refuser', name: 'app_responsable_livrable_reject', methods: ['POST'])]
public function rejectLivrable(Request $request, LivrableChallenge $livrable, EntityManagerInterface $entityManager, NotificationChallengeService $notifService): Response
{
        if ($redirect = $this->checkRole()) return $redirect;
    if ($this->isCsrfTokenValid('reject'.$livrable->getId(), is_string($token = $request->request->get('_token')) ? $token : null)) {
        if ($livrable->getStatut() === 'refuse') {
            $this->addFlash('error', 'Ce livrable est déjà refusé ! ❌');
            return $this->redirectToRoute('app_responsable_livrables');
        }
        
        $livrable->setStatut('refuse');
        $livrable->setDateEvaluation(new \DateTime());
        $entityManager->flush();
        
        // ✅ NOTIFIER LES ÉTUDIANTS DU REFUS
        $groupe = $livrable->getGroupe();
        if ($groupe) {
            foreach ($groupe->getMembres() as $membreGroupe) {
                $etudiant = $membreGroupe->getUser();
                $notifService->notifierRefusLivrable(
                    $this->getUser(),
                    $etudiant,
                    $livrable->getChallenge()?->getTitre() ?? ''
                );
            }
        }
        $this->addFlash('success', 'Livrable refusé ! ❌ Le groupe devra soumettre un nouveau livrable.');
    }
    
    return $this->redirectToRoute('app_responsable_livrables');
}

#[Route('/challenges/{id}', name: 'app_responsable_challenge_show', requirements: ['id' => '\d+'])]
public function showChallenge(Challenge $challenge): Response
{
        if ($redirect = $this->checkRole()) return $redirect;
    return $this->render('frontoffice/responsable/challenges/show.html.twig', [
        'challenge' => $challenge,
    ]);
}

#[Route('/challenges/{id}/modifier', name: 'app_responsable_challenge_edit', methods: ['GET', 'POST'])]
public function editChallenge(Request $request, Challenge $challenge, EntityManagerInterface $entityManager): Response
{
        if ($redirect = $this->checkRole()) return $redirect;
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
        if ($redirect = $this->checkRole()) return $redirect;
    if ($this->isCsrfTokenValid('delete'.$challenge->getId(), is_string($token = $request->request->get('_token')) ? $token : null)) {
        
        // Vérifier si le challenge a des participations
        if ($challenge->getParticipations()->count() > 0) {
            $this->addFlash('error', 'Impossible de supprimer ce challenge : des groupes y participent. Supprimez d\'abord les participations.');
            return $this->redirectToRoute('app_responsable_challenges');
        }
        
        // Supprimer les fichiers uploadés
        if ($challenge->getImage()) {
            /** @var string $uploadsDir */
            $uploadsDir = $this->getParameter('uploads_directory');
            $imagePath = $uploadsDir . '/' . $challenge->getImage();
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }
        
        if ($challenge->getFichierCahierCharges()) {
            /** @var string $uploadsDir */
            $uploadsDir = $this->getParameter('uploads_directory');
            $pdfPath = $uploadsDir . '/' . $challenge->getFichierCahierCharges();
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

#[Route('/challenges/generer-avec-ia', name: 'app_responsable_challenge_generate_ai', methods: ['GET', 'POST'])]
public function generateChallengeWithAI(Request $request, GroqService $groqService): Response
{
    if ($redirect = $this->checkRole()) return $redirect;
    
    $form = $this->createForm(ChallengeAIType::class);
    $form->handleRequest($request);
    
    $generatedData = null;
    $error = null;

    if ($form->isSubmitted() && $form->isValid()) {
        $data = $form->getData();
        
        // Appeler Groq pour générer le challenge
        $generatedData = $groqService->generateChallenge($data);
        
        if (!$generatedData) {
            $error = "Erreur lors de la génération. Veuillez réessayer.";
        } else {
            // Sauvegarder en session pour la prévisualisation
            $request->getSession()->set('generated_challenge', $generatedData);
        }
    }

    return $this->render('frontoffice/responsable/challenges/generate_ai.html.twig', [
        'form' => $form,
        'generatedData' => $generatedData,
        'error' => $error,
    ]);
}

#[Route('/challenges/creer-depuis-ia', name: 'app_responsable_challenge_create_from_ai', methods: ['POST'])]
public function createChallengeFromAI(
    Request $request, 
    EntityManagerInterface $entityManager,
    PdfGeneratorService $pdfGenerator,
    NotificationServicech $notificationService
): Response {
    if ($redirect = $this->checkRole()) return $redirect;
    
    $generatedData = $request->getSession()->get('generated_challenge');
    
    if (!$generatedData) {
        $this->addFlash('error', 'Aucune génération trouvée.');
        return $this->redirectToRoute('app_responsable_challenge_generate_ai');
    }
    
    $titre = (string) $request->request->get('titre', '');
    $description = $request->request->get('description') ? (string) $request->request->get('description') : null;
    $dateDebut = new \DateTime((string) $request->request->get('dateDebut'));
    $dateFin = new \DateTime((string) $request->request->get('dateFin'));
    
    // ✅ Générer le PDF du cahier des charges
    $pdfContent = $pdfGenerator->generateCahierChargesPDF($generatedData, $titre);
    $pdfFilename = 'cahier_charges_' . uniqid() . '.pdf';
    /** @var string $uploadsDir */
    $uploadsDir = $this->getParameter('uploads_directory');
    $pdfPath = $uploadsDir . '/' . $pdfFilename;
    file_put_contents($pdfPath, $pdfContent);
    
    // Créer le challenge
    $challenge = new Challenge();
    $challenge->setTitre($titre);
    $challenge->setDescription($description);
    $challenge->setDateDebut($dateDebut);
    $challenge->setDateFin($dateFin);
    $challenge->setFichierCahierCharges($pdfFilename);
    
    // ✅ NOUVEAU : Gérer l'upload de l'image
    $imageFile = $request->files->get('image');
    if ($imageFile) {
        $newFilename = uniqid() . '.' . $imageFile->guessExtension();
        try {
            $imageFile->move(
                $this->getParameter('uploads_directory'),
                $newFilename
            );
            $challenge->setImage($newFilename);
        } catch (FileException $e) {
            $this->addFlash('warning', 'Challenge créé mais erreur lors de l\'upload de l\'image.');
        }
    }
    
    $entityManager->persist($challenge);
    $entityManager->flush();

    // ← Ajouter ce bloc
$groupes = $entityManager->getRepository(Groupe::class)->findAll();
foreach ($groupes as $groupe) {
    foreach ($groupe->getMembres() as $membre) {
        $user = $membre->getUser();
        $dateDebut = $challenge->getDateDebut();
        $dateFin = $challenge->getDateFin();
        if ($user && $user->getEmail() && $dateDebut && $dateFin) {
            $notificationService->envoyerEmailNouveauChallenge(
                $user->getEmail(),
                $user->getNom() . ' ' . $user->getPrenom(),
                $challenge->getTitre() ?? '',
                $dateDebut->format('d/m/Y'),
                $dateFin->format('d/m/Y')
            );
        }
    }
}
    
    $request->getSession()->remove('generated_challenge');
    
    $successMessage = '🤖 Challenge généré par l\'IA créé avec succès !';
    if ($imageFile) {
        $successMessage .= ' 📸 Image ajoutée.';
    }
    $successMessage .= ' 📄 PDF généré automatiquement.';
    
    $this->addFlash('success', $successMessage);
    return $this->redirectToRoute('app_responsable_challenges');
}

#[Route('/livrable/{id}/evaluer', name: 'app_responsable_livrable_evaluate', methods: ['GET', 'POST'])]
public function evaluateLivrable(
    Request $request, 
    LivrableChallenge $livrable, 
    EntityManagerInterface $entityManager,
    NotificationChallengeService $notifService
): Response {
    if ($redirect = $this->checkRole()) return $redirect;
    
    // Récupérer les membres du groupe
    $groupe = $livrable->getGroupe();
    if (!$groupe) {
        $this->addFlash('error', 'Groupe introuvable.');
        return $this->redirectToRoute('app_responsable_livrables');
    }
    $membres = $groupe->getMembres();
    
    // Créer le formulaire d'évaluation
    $form = $this->createForm(EvaluationLivrableType::class);
    $form->handleRequest($request);
    
    if ($form->isSubmitted() && $form->isValid()) {
        $data = $form->getData();
        
        // Sauvegarder les notes par critère
        $livrable->setNoteCode($data['noteCode']);
        $livrable->setNoteFonctionnalites($data['noteFonctionnalites']);
        $livrable->setNoteDesign($data['noteDesign']);
        $livrable->setNoteDocumentation($data['noteDocumentation']);
        
        // Calculer la note globale (moyenne)
        $noteGlobale = ($data['noteCode'] + $data['noteFonctionnalites'] + $data['noteDesign'] + $data['noteDocumentation']) / 4;
        $livrable->setNoteGlobale(number_format($noteGlobale, 2));
        
        // Sauvegarder le feedback
        $livrable->setPointsForts($data['pointsForts']);
        $livrable->setPointsAmeliorer($data['pointsAmeliorer']);
        $livrable->setSuggestions($data['suggestions']);
        
        // Sauvegarder les évaluations individuelles
        foreach ($membres as $membreGroupe) {
            $user = $membreGroupe->getUser();
            $noteIndiv = $request->request->get('note_' . $user->getId());
            $commentaireIndiv = $request->request->get('commentaire_' . $user->getId());
            
            if ($noteIndiv !== null && $noteIndiv !== '') {
                $evaluation = new EvaluationMembre();
                $evaluation->setLivrable($livrable);
                $evaluation->setUser($user);
                $evaluation->setNoteIndividuelle(is_string($noteIndiv) ? $noteIndiv : (string) $noteIndiv);
                $evaluation->setCommentaireIndividuel(is_string($commentaireIndiv) ? $commentaireIndiv : ($commentaireIndiv ? (string) $commentaireIndiv : null));
                
                $entityManager->persist($evaluation);
            }
        }
        
        // Mettre le statut et la date d'évaluation
        $livrable->setStatut('valide');
        $livrable->setDateEvaluation(new \DateTime());
        
        $entityManager->flush();

        // ✅ ENVOYER LES NOTIFICATIONS AUX ÉTUDIANTS
        foreach ($membres as $membreGroupe) {
            $etudiant = $membreGroupe->getUser();
            $notifService->notifierEvaluationLivrable(
                $this->getUser(), // Le responsable
                $etudiant,        // L'étudiant
                $livrable->getChallenge()?->getTitre() ?? '',
                $noteGlobale
            );
        }
        
        $this->addFlash('success', '✅ Livrable évalué avec succès ! Notes enregistrées.');
        return $this->redirectToRoute('app_responsable_livrables');
    }
    
    return $this->render('frontoffice/responsable/livrables/evaluate.html.twig', [
        'livrable' => $livrable,
        'form' => $form,
        'membres' => $membres,
    ]);
}
#[Route('/livrable/{id}/voir-evaluation', name: 'app_responsable_livrable_voir_evaluation')]
public function voirEvaluation(LivrableChallenge $livrable): Response
{
    if ($redirect = $this->checkRole()) return $redirect;
    
    // Vérifier que le livrable a bien été évalué
    if ($livrable->getStatut() !== 'valide') {
        $this->addFlash('error', 'Ce livrable n\'a pas encore été évalué.');
        return $this->redirectToRoute('app_responsable_livrables');
    }
    
    return $this->render('frontoffice/responsable/livrables/voir_evaluation.html.twig', [
        'livrable' => $livrable,
    ]);
}

}