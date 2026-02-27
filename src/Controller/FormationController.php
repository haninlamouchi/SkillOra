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
use App\Repository\ResultatQuizRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use App\Service\CloudinaryService;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;




#[Route('/responsable/formation')]
final class FormationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private FormationRepository $formationRepository,
        private SluggerInterface $slugger,
        private MailerInterface $mailer,
    ) {}

    // ──────────────────────────────────────────────
    //  HELPER : club du responsable connecté
    //  ✅ FIX: getClubResponsable() n'existe pas → on utilise getClubs()->first()
    // ──────────────────────────────────────────────

    private function getMyClub(): ?\App\Entity\Club
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $club = $user->getClubs()->first();
        return $club ?: null;
    }

    // ──────────────────────────────────────────────
    //  LIST
    // ──────────────────────────────────────────────

    #[Route('', name: 'app_formation_index', methods: ['GET'])]
    public function index(): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (in_array('ROLE_RESPONSABLE_CLUB', $user->getRoles())) {
            // ✅ FIX: remplace getClubResponsable() par getClubs()->first()
            $club = $this->getMyClub();
            $formations = $club
                ? $this->formationRepository->findBy(['club' => $club], ['dateDebut' => 'DESC'])
                : [];
        } else {
            $formations = $this->formationRepository->findAll();
        }

        return $this->render('frontoffice/responsable/formation/index.html.twig', [
            'formations' => $formations,
        ]);
    }

    // ──────────────────────────────────────────────
    //  ADD
    // ──────────────────────────────────────────────

    #[Route('/new', name: 'app_formation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, CloudinaryService $cloudinary): Response
    {
        $formation = new Formation();
        $form = $this->createForm(FormationType::class, $formation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();

            if ($imageFile) {
                $url = $cloudinary->uploadImage($imageFile);
                $formation->setImage($url);
            }

            $formation->setClub($this->getMyClub());

            $this->em->persist($formation);
            $this->em->flush();

            // Get club members
            $club = $formation->getClub();
            $members = $club->getMembres();

            foreach ($members as $member) {

                if ($member->getEmail()) {

                    $email = (new Email())
                        ->from('koukieya43@gmail.com')
                        ->to($member->getEmail())
                        ->subject('Nouvelle formation ajoutée')
                        ->html('
                            <div style="font-family: Arial, sans-serif; background-color: #f4f6f9; padding: 40px 0;">
                                <div style="max-width: 600px; margin: auto; background: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">

                                    <!-- Header -->
                                    <div style="background: linear-gradient(90deg, #4e73df, #1cc88a); padding: 20px; text-align: center;">
                                        <h1 style="color: #ffffff; margin: 0;">📚 Nouvelle Formation</h1>
                                    </div>

                                    <!-- Body -->
                                    <div style="padding: 30px; color: #333333;">
                                        <p style="font-size: 16px;">Bonjour 👋,</p>

                                        <p style="font-size: 15px; line-height: 1.6;">
                                            Nous avons le plaisir de vous informer qu’une nouvelle formation a été ajoutée à votre club.
                                        </p>

                                        <div style="background-color: #f8f9fc; padding: 15px; border-left: 5px solid #4e73df; margin: 20px 0;">
                                            <p style="margin: 5px 0;"><strong>Titre :</strong> '.$formation->getTitre().'</p>
                                            <p style="margin: 5px 0;"><strong>Description :</strong> '.$formation->getDescription().'</p>
                                        </div>

                                        <p style="font-size: 14px; color: #6c757d;">
                                            Nous vous encourageons à consulter les détails et à vous inscrire si cela vous intéresse.
                                        </p>
                                    </div>

                                    <!-- Footer -->
                                    <div style="background-color: #f1f1f1; text-align: center; padding: 15px; font-size: 12px; color: #888;">
                                        © '.date("Y").' SkillOra | Tous droits réservés<br>
                                        Cet email a été envoyé automatiquement, merci de ne pas y répondre.
                                    </div>

                                </div>
                            </div>
                            ');

                    $this->mailer->send($email);
                }
            }

            $this->addFlash('success', 'Formation créée avec succès.');

            return $this->redirectToRoute('app_formation_index');
        }

        return $this->render('frontoffice/responsable/formation/new.html.twig', [
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
        ResultatQuizRepository $resultatRepo,
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

        // --- Quiz results for this formation ---
        $quizResults = [];
        $participantScores = [];

        foreach ($formation->getQuizzes() as $quiz) {
            $results = $resultatRepo->findBy(['quiz' => $quiz], ['datePassage' => 'DESC']);
            foreach ($results as $result) {
                $quizResults[] = $result;

                $uid = $result->getUser()->getId();
                $pct = $result->getTotalPoints() > 0
                    ? round(($result->getScore() / $result->getTotalPoints()) * 100)
                    : 0;

                if (!isset($participantScores[$uid]) || $pct > $participantScores[$uid]['pct']) {
                    $participantScores[$uid] = [
                        'score'       => $result->getScore(),
                        'totalPoints' => $result->getTotalPoints(),
                        'pct'         => $pct,
                        'quizTitre'   => $result->getQuiz()->getTitre(),
                    ];
                }
            }
        }

        return $this->render('frontoffice/responsable/formation/show.html.twig', [
            'formation'         => $formation,
            'videoForm'         => $videoForm,
            'participationForm' => $participationForm,
            'quizResults'       => $quizResults,
            'participantScores' => $participantScores,
        ]);
    }

    // ──────────────────────────────────────────────
    //  EDIT
    // ──────────────────────────────────────────────

    #[Route('/{id}/edit', name: 'app_formation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Formation $formation, CloudinaryService $cloudinary): Response
    {
        $form = $this->createForm(FormationType::class, $formation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $url = $cloudinary->uploadImage($imageFile);
                $formation->setImage($url);
            }

            

            $this->em->flush();

            $this->addFlash('success', 'Formation modifiée avec succès.');

            return $this->redirectToRoute('app_formation_show', ['id' => $formation->getId()]);
        }

        return $this->render('frontoffice/responsable/formation/edit.html.twig', [
            'formation' => $formation,
            'form' => $form,
        ]);
    }

    // ──────────────────────────────────────────────
    //  DELETE
    // ──────────────────────────────────────────────

    #[Route('/{id}/delete', name: 'app_formation_delete',methods: ['GET', 'POST'])]
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
    public function addVideo(Request $request, Formation $formation,CloudinaryService $cloudinary): Response
    {
        $video = new Video();
        $form = $this->createForm(VideoType::class, $video);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            //$this->handleVideoUpload($form, $video);
            $videoFile = $form->get('videoFile')->getData();

            if (!$videoFile) {
                $this->addFlash('danger', 'Erreur lors de l\'upload du fichier vidéo.');
                return $this->redirectToRoute('app_formation_show', ['id' => $formation->getId()]);
            }
            if($videoFile){
                $url = $cloudinary->uploadVideo($videoFile);
                $video->setVideoPath($url);
            }
 
            $video->setFormation($formation);
            $this->em->persist($video);
            $this->em->flush();


            $participants = $formation->getParticipations();

            foreach ($participants as $participation) {

                $member = $participation->getUser();

                if ($member && $member->getEmail()) {

                    $email = (new Email())
                        ->from('koukieya43@gmail.com')
                        ->to($member->getEmail())
                        ->subject('Nouvelle vidéo ajoutée')
                        ->html('
                            <div style="font-family: Arial, sans-serif; background-color: #f4f6f9; padding: 40px 0;">
                                <div style="max-width: 600px; margin: auto; background: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">

                                    <div style="background: linear-gradient(90deg, #4e73df, #1cc88a); padding: 20px; text-align: center;">
                                        <h1 style="color: #ffffff; margin: 0;">🎥 Nouvelle Vidéo</h1>
                                    </div>

                                    <div style="padding: 30px; color: #333333;">

                                        <p style="font-size: 16px;">Bonjour 👋,</p>

                                        <p style="font-size: 15px;">
                                            Une nouvelle vidéo a été ajoutée dans votre formation.
                                        </p>

                                        <div style="background-color: #f8f9fc; padding: 15px; border-left: 5px solid #4e73df; margin: 20px 0;">
                                            <p><strong>Formation :</strong> '.$formation->getTitre().'</p>
                                            <p><strong>Vidéo :</strong> '.$video->getTitre().'</p>
                                        </div>

                                        <p style="font-size: 14px; color: #6c757d;">
                                            Connectez-vous pour voir la vidéo.
                                        </p>

                                    </div>

                                    <div style="background-color: #f1f1f1; text-align: center; padding: 15px; font-size: 12px; color: #888;">
                                        © '.date("Y").' SkillOra
                                    </div>

                                </div>
                            </div>
                        ');

                    $this->mailer->send($email);
                }
            }




            $this->addFlash('success', 'Vidéo ajoutée avec succès.');
        } elseif ($form->isSubmitted()) {
            $errors = [];
            foreach ($form->getErrors(true) as $error) {
                $errors[] = $error->getMessage();
            }
            $this->addFlash('danger', 'Erreur : ' . (implode(', ', $errors) ?: 'Fichier vidéo invalide ou trop volumineux.'));
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

            if ($participationRepo->isAlreadyParticipating($user, $formation)) {
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

                $oldImage = $formation->getImage();
                if ($oldImage) {
                    $oldPath = $this->getParameter('kernel.project_dir') . '/public' . $oldImage;
                    if (file_exists($oldPath)) {
                        unlink($oldPath);
                    }
                }

                $formation->setImage('/uploads/formations/' . $newFilename);
            } catch (FileException $e) {
                // Handle exception silently
            }
        }
    }

    // ──────────────────────────────────────────────
    //  PRIVATE: Handle video upload
    // ──────────────────────────────────────────────

    /*private function handleVideoUpload($form, Video $video): void
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
                // Handle exception silently
            }
        }
    }*/
}
