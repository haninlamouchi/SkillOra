<?php

namespace App\Controller;

use App\Repository\PublicationRepository;
use App\Repository\CommentaireRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/membre')]
#[IsGranted('ROLE_MEMBRE')]
class MembreController extends AbstractController
{
    #[Route('/dashboard', name: 'app_membre_dashboard')]
    public function dashboard(
        PublicationRepository $publicationRepo,
        CommentaireRepository $commentaireRepo
    ): Response {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        // Publications du membre
        $mesPublications = $publicationRepo->findBy(['user' => $user]);
        $publiees        = array_filter($mesPublications, fn($p) => $p->getStatus() === \App\Enum\StatusPublication::PUBLIE);
        $enAttente       = array_filter($mesPublications, fn($p) => $p->getStatus() === \App\Enum\StatusPublication::EN_ATTENTE);

        // Commentaires du membre
        $mesCommentaires = $commentaireRepo->findBy(['user' => $user]);

        return $this->render('membre/dashboard.html.twig', [
            'user'              => $user,
            'currentUser'       => $user,           // ✅ pour la navbar
            'totalPublications' => count($mesPublications),
            'publiees'          => count($publiees),
            'enAttente'         => count($enAttente),
            'totalCommentaires' => count($mesCommentaires),
        ]);
    }
}