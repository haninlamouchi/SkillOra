<?php

namespace App\Controller;

use App\Repository\PublicationRepository;
use App\Repository\CommentaireRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/responsable')]
#[IsGranted('ROLE_RESPONSABLE_CLUB')]
class ResponsableClubController extends AbstractController
{
    #[Route('/dashboard', name: 'app_responsable_dashboard')]
    public function dashboard(
        PublicationRepository $publicationRepo,
        CommentaireRepository $commentaireRepo
    ): Response {
        $user = $this->getUser();

        return $this->render('responsable/dashboard.html.twig', [
            'user'              => $user,
            'currentUser'       => $user,   // ← LA LIGNE QUI MANQUAIT
            'totalPublications' => count($publicationRepo->findAll()),
            'enAttente'         => count($publicationRepo->findBy(['status' => \App\Enum\StatusPublication::EN_ATTENTE])),
            'publiees'          => count($publicationRepo->findBy(['status' => \App\Enum\StatusPublication::PUBLIE])),
            'totalCommentaires' => count($commentaireRepo->findAll()),
        ]);
    }
}