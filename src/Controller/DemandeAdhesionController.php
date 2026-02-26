<?php

namespace App\Controller;

use App\Entity\DemandeAdhesion;
use App\Form\DemandeAdhesionType;
use App\Repository\ClubRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/demande-adhesion')]
class DemandeAdhesionController extends AbstractController
{
    #[Route('/new/{clubId}', name: 'app_demande_adhesion_new', requirements: ['clubId' => '\d+'])]
    public function new(
        int $clubId,
        Request $request,
        ClubRepository $clubRepository,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response {
        // Vérifier que l'utilisateur est connecté
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        $user = $this->getUser();
        $club = $clubRepository->find($clubId);

        if (!$club) {
            throw $this->createNotFoundException('Club introuvable.');
        }

        // Vérifier si l'utilisateur a déjà une demande en attente pour ce club
        $existingDemande = $entityManager->getRepository(DemandeAdhesion::class)
            ->findOneBy(['user' => $user, 'club' => $club, 'statut' => 'en_attente']);

        if ($existingDemande) {
            $this->addFlash('warning', 'Vous avez déjà une demande en attente pour ce club.');
            return $this->redirectToRoute('front_home_user', ['userId' => $user->getId()]);
        }

        $demande = new DemandeAdhesion();
        $demande->setUser($user);
        $demande->setClub($club);

        $form = $this->createForm(DemandeAdhesionType::class, $demande);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gérer l'upload du CV
            $cvFile = $form->get('cv')->getData();
            if ($cvFile) {
                $originalFilename = pathinfo($cvFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$cvFile->guessExtension();

                try {
                    $cvFile->move(
                        $this->getParameter('kernel.project_dir').'/public/uploads/cv',
                        $newFilename
                    );
                    $demande->setCv($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload du CV.');
                }
            }

            // Gérer l'upload de la lettre de motivation
            $lettreFile = $form->get('lettreMotivation')->getData();
            if ($lettreFile) {
                $originalFilename = pathinfo($lettreFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$lettreFile->guessExtension();

                try {
                    $lettreFile->move(
                        $this->getParameter('kernel.project_dir').'/public/uploads/lettres',
                        $newFilename
                    );
                    $demande->setLettreMotivation($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de la lettre de motivation.');
                }
            }

            $entityManager->persist($demande);
            $entityManager->flush();

            $this->addFlash('success', '✅ Votre candidature a été envoyée avec succès ! Le responsable du club examinera votre demande.');

            return $this->redirectToRoute('front_home_user', ['userId' => $user->getId()]);
        }

        return $this->render('demande_adhesion/new.html.twig', [
            'form' => $form,
            'club' => $club,
            'currentUser' => $user,
        ]);
    }
}
