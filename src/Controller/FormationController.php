<?php

namespace App\Controller;

use App\Entity\Formation;
use App\Entity\ParticipationFormation;
use App\Entity\Video;
use App\Form\FormationType;
use App\Form\ParticipationFormationType;
use App\Form\VideoType;
use App\Repository\FormationRepository;
use App\Repository\ParticipationFormationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/formation')]
final class FormationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private FormationRepository $formationRepository,
        private SluggerInterface $slugger,
    ) {}

    // ──────────────────────────────────────────────
    //  LIST
    // ──────────────────────────────────────────────

    #[Route('', name: 'app_formation_index', methods: ['GET'])]
    public function index(): Response
    {
        $formations = $this->formationRepository->findAll();

        return $this->render('backoffice/formation/index.html.twig', [
            'formations' => $formations,
        ]);
    }

    // ──────────────────────────────────────────────
    //  ADD
    // ──────────────────────────────────────────────

    #[Route('/new', name: 'app_formation_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $formation = new Formation();
        $form = $this->createForm(FormationType::class, $formation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleImageUpload($form, $formation);

            $this->em->persist($formation);
            $this->em->flush();

            $this->addFlash('success', 'Formation créée avec succès.');

            return $this->redirectToRoute('app_formation_index');
        }

        return $this->render('backoffice/formation/new.html.twig', [
            'formation' => $formation,
            'form' => $form,
        ]);
    }

    // ──────────────────────────────────────────────
    //  SHOW (details + videos + participants)
    // ──────────────────────────────────────────────

    #[Route('/{id}', name: 'app_formation_show', methods: ['GET', 'POST'])]
    public function show(
        Formation $formation,
        Request $request,
        ParticipationFormationRepository $participationRepo,
    ): Response {
        // --- Video form (for Bootstrap modal) ---
        $video = new Video();
        $videoForm = $this->createForm(VideoType::class, $video, [
            'action' => $this->generateUrl('app_formation_add_video', ['id' => $formation->getId()]),
        ]);

        // --- Participation form ---
        $participation = new ParticipationFormation();
        $participationForm = $this->createForm(ParticipationFormationType::class, $participation, [
            'action' => $this->generateUrl('app_formation_add_participant', ['id' => $formation->getId()]),
        ]);

        return $this->render('backoffice/formation/show.html.twig', [
            'formation' => $formation,
            'videoForm' => $videoForm,
            'participationForm' => $participationForm,
        ]);
    }

    // ──────────────────────────────────────────────
    //  EDIT
    // ──────────────────────────────────────────────

    #[Route('/{id}/edit', name: 'app_formation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Formation $formation): Response
    {
        $form = $this->createForm(FormationType::class, $formation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleImageUpload($form, $formation);

            $this->em->flush();

            $this->addFlash('success', 'Formation modifiée avec succès.');

            return $this->redirectToRoute('app_formation_show', ['id' => $formation->getId()]);
        }

        return $this->render('backoffice/formation/edit.html.twig', [
            'formation' => $formation,
            'form' => $form,
        ]);
    }

    // ──────────────────────────────────────────────
    //  DELETE
    // ──────────────────────────────────────────────

    #[Route('/{id}/delete', name: 'app_formation_delete', methods: ['POST'])]
    public function delete(Request $request, Formation $formation): Response
    {
        if ($this->isCsrfTokenValid('delete' . $formation->getId(), $request->request->get('_token'))) {
            $this->em->remove($formation);
            $this->em->flush();

            $this->addFlash('success', 'Formation supprimée avec succès.');
        }

        return $this->redirectToRoute('app_formation_index');
    }

    // ──────────────────────────────────────────────
    //  ADD VIDEO (from modal)
    // ──────────────────────────────────────────────

    #[Route('/{id}/video/add', name: 'app_formation_add_video', methods: ['POST'])]
    public function addVideo(Request $request, Formation $formation): Response
    {
        $video = new Video();
        $form = $this->createForm(VideoType::class, $video);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleVideoUpload($form, $video);

            if (!$video->getVideoPath()) {
                $this->addFlash('danger', 'Erreur lors de l\'upload du fichier vidéo.');
                return $this->redirectToRoute('app_formation_show', ['id' => $formation->getId()]);
            }

            $video->setFormation($formation);
            $this->em->persist($video);
            $this->em->flush();

            $this->addFlash('success', 'Vidéo ajoutée avec succès.');
        } else if ($form->isSubmitted()) {
            $errors = [];
            foreach ($form->getErrors(true) as $error) {
                $errors[] = $error->getMessage();
            }
            $this->addFlash('danger', 'Erreur : ' . (implode(', ', $errors) ?: 'Fichier vidéo invalide ou trop volumineux. Vérifiez la taille du fichier.'));
        }

        return $this->redirectToRoute('app_formation_show', ['id' => $formation->getId()]);
    }

    // ──────────────────────────────────────────────
    //  DELETE VIDEO
    // ──────────────────────────────────────────────

    #[Route('/{id}/video/{videoId}/delete', name: 'app_formation_delete_video', methods: ['POST'])]
    public function deleteVideo(Request $request, Formation $formation, int $videoId): Response
    {
        $video = $this->em->getRepository(Video::class)->find($videoId);

        if ($video && $video->getFormation() === $formation) {
            if ($this->isCsrfTokenValid('delete_video' . $videoId, $request->request->get('_token'))) {
                // Delete physical video file
                $videoPath = $video->getVideoPath();
                if ($videoPath) {
                    $fullPath = $this->getParameter('kernel.project_dir') . '/public' . $videoPath;
                    if (file_exists($fullPath)) {
                        unlink($fullPath);
                    }
                }

                $this->em->remove($video);
                $this->em->flush();

                $this->addFlash('success', 'Vidéo supprimée.');
            }
        }

        return $this->redirectToRoute('app_formation_show', ['id' => $formation->getId()]);
    }

    // ──────────────────────────────────────────────
    //  ADD PARTICIPANT
    // ──────────────────────────────────────────────

    #[Route('/{id}/participant/add', name: 'app_formation_add_participant', methods: ['POST'])]
    public function addParticipant(
        Request $request,
        Formation $formation,
        ParticipationFormationRepository $participationRepo,
    ): Response {
        $participation = new ParticipationFormation();
        $form = $this->createForm(ParticipationFormationType::class, $participation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $participation->getUser();

            // Check duplicate participation
            if ($participationRepo->isAlreadyParticipating($user->getId(), $formation->getId())) {
                $this->addFlash('warning', 'Cet utilisateur participe déjà à cette formation.');
            } else {
                $participation->setFormation($formation);
                $this->em->persist($participation);
                $this->em->flush();

                $this->addFlash('success', 'Participant ajouté avec succès.');
            }
        }

        return $this->redirectToRoute('app_formation_show', ['id' => $formation->getId()]);
    }

    // ──────────────────────────────────────────────
    //  REMOVE PARTICIPANT
    // ──────────────────────────────────────────────

    #[Route('/{id}/participant/{participationId}/remove', name: 'app_formation_remove_participant', methods: ['POST'])]
    public function removeParticipant(
        Request $request,
        Formation $formation,
        int $participationId,
    ): Response {
        $participation = $this->em->getRepository(ParticipationFormation::class)->find($participationId);

        if ($participation && $participation->getFormation() === $formation) {
            if ($this->isCsrfTokenValid('remove_participant' . $participationId, $request->request->get('_token'))) {
                $this->em->remove($participation);
                $this->em->flush();

                $this->addFlash('success', 'Participant retiré.');
            }
        }

        return $this->redirectToRoute('app_formation_show', ['id' => $formation->getId()]);
    }

    // ──────────────────────────────────────────────
    //  PRIVATE: Handle image upload
    // ──────────────────────────────────────────────

    private function handleImageUpload($form, Formation $formation): void
    {
        $imageFile = $form->get('imageFile')->getData();

        if ($imageFile) {
            $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $this->slugger->slug($originalFilename);
            $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

            $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/formations';

            try {
                $imageFile->move($uploadDir, $newFilename);

                // Delete old image if exists
                $oldImage = $formation->getImage();
                if ($oldImage) {
                    $oldPath = $this->getParameter('kernel.project_dir') . '/public' . $oldImage;
                    if (file_exists($oldPath)) {
                        unlink($oldPath);
                    }
                }

                $formation->setImage('/uploads/formations/' . $newFilename);
            } catch (FileException $e) {
                // Handle exception silently - image won't be updated
            }
        }
    }

    // ──────────────────────────────────────────────
    //  PRIVATE: Handle video upload
    // ──────────────────────────────────────────────

    private function handleVideoUpload($form, Video $video): void
    {
        $videoFile = $form->get('videoFile')->getData();

        if ($videoFile) {
            $originalFilename = pathinfo($videoFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $this->slugger->slug($originalFilename);
            $newFilename = $safeFilename . '-' . uniqid() . '.' . $videoFile->guessExtension();

            $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/videos';

            try {
                $videoFile->move($uploadDir, $newFilename);
                $video->setVideoPath('/uploads/videos/' . $newFilename);
            } catch (FileException $e) {
                // Handle exception silently - video won't be saved
            }
        }
    }
}