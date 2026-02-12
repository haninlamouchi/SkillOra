<?php

namespace App\Controller;

use App\Entity\Commentaire;
use App\Entity\Publication;
use App\Form\PublicationType;
use App\Repository\CommentaireRepository;
use App\Repository\PublicationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/forum')]
class AdminPublicationController extends AbstractController
{
    // ═══════════════════════════════════════════
    //  PUBLICATIONS
    // ═══════════════════════════════════════════

    #[Route('/publications', name: 'admin_forum_publications', methods: ['GET'])]
    public function publications(PublicationRepository $repo, Request $request): Response
    {
        $search = $request->query->get('q', '');
        $sort   = $request->query->get('sort', 'datePublication');
        $dir    = $request->query->get('dir', 'DESC');

        return $this->render('backoffice/forum/publications.html.twig', [
            'publications' => $repo->searchAndSort($search, $sort, $dir),
            'search'       => $search,
            'sort'         => $sort,
            'dir'          => $dir,
        ]);
    }

    #[Route('/publications/{id}/edit', name: 'admin_forum_publication_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function publicationEdit(Publication $publication, Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(PublicationType::class, $publication, [
            'show_status' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $fichierFile = $form->get('fichier')->getData();
            if ($fichierFile) {
                $originalFilename = pathinfo($fichierFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $fichierFile->guessExtension();

                try {
                    $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/publications';
                    $fichierFile->move($uploadDir, $newFilename);

                    $oldFile = $publication->getFichier();
                    if ($oldFile) {
                        $oldPath = $uploadDir . '/' . $oldFile;
                        if (file_exists($oldPath)) {
                            unlink($oldPath);
                        }
                    }
                    $publication->setFichier($newFilename);
                } catch (FileException $e) {
                    // silently ignore
                }
            }

            $em->flush();
            return $this->redirectToRoute('admin_forum_publications');
        }

        return $this->render('backoffice/forum/publication_edit.html.twig', [
            'publication' => $publication,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/publications/{id}/delete', name: 'admin_forum_publication_delete', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function publicationDelete(Publication $publication, EntityManagerInterface $em): Response
    {
        $fichier = $publication->getFichier();
        if ($fichier) {
            $filePath = $this->getParameter('kernel.project_dir') . '/public/uploads/publications/' . $fichier;
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        $em->remove($publication);
        $em->flush();

        return $this->redirectToRoute('admin_forum_publications');
    }

    // ═══════════════════════════════════════════
    //  COMMENTAIRES
    // ═══════════════════════════════════════════

    #[Route('/commentaires', name: 'admin_forum_commentaires', methods: ['GET'])]
    public function commentaires(CommentaireRepository $repo, Request $request): Response
    {
        $search = $request->query->get('q', '');
        $sort   = $request->query->get('sort', 'dateCommentaire');
        $dir    = $request->query->get('dir', 'DESC');

        return $this->render('backoffice/forum/commentaires.html.twig', [
            'commentaires' => $repo->searchAndSort($search, $sort, $dir),
            'search'       => $search,
            'sort'         => $sort,
            'dir'          => $dir,
        ]);
    }

    #[Route('/commentaires/{id}/edit', name: 'admin_forum_commentaire_edit', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function commentaireEdit(Commentaire $commentaire, Request $request, EntityManagerInterface $em): Response
    {
        $contenu = $request->request->get('contenu');
        if ($contenu && trim($contenu) !== '') {
            $commentaire->setContenu(trim($contenu));
            $em->flush();
        }

        return $this->redirectToRoute('admin_forum_commentaires');
    }

    #[Route('/commentaires/{id}/delete', name: 'admin_forum_commentaire_delete', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function commentaireDelete(Commentaire $commentaire, EntityManagerInterface $em): Response
    {
        $em->remove($commentaire);
        $em->flush();

        return $this->redirectToRoute('admin_forum_commentaires');
    }
}