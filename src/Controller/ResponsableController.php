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


#[Route('/responsable')]
final class ResponsableController extends AbstractController
{
    #[Route('/dashboard', name: 'app_responsable_dashboard')]
    public function dashboard(): Response
    {
        return $this->render('frontoffice/responsable/dashboard.html.twig');
    }

    #[Route('/challenges', name: 'app_responsable_challenges')]
    public function listChallenges(ChallengeRepository $challengeRepository): Response
    {
        return $this->render('frontoffice/responsable/challenges/list.html.twig', [
            'challenges' => $challengeRepository->findAll(),
        ]);
    }

    #[Route('/challenges/nouveau', name: 'app_responsable_challenge_new', methods: ['GET', 'POST'])]
    public function newChallenge(Request $request, EntityManagerInterface $entityManager): Response
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

            $this->addFlash('success', 'Challenge créé avec succès !');
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
public function listLivrables(LivrableChallengeRepository $livrableRepository): Response
{
    $livrables = $livrableRepository->findAll();
    
    return $this->render('frontoffice/responsable/livrables/list.html.twig', [
        'livrables' => $livrables,
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
}