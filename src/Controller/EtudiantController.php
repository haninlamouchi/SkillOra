<?php

namespace App\Controller;

use App\Entity\Challenge;
use App\Entity\Groupe;
use App\Entity\Participation;
use App\Repository\ChallengeRepository;
use App\Repository\GroupeRepository;
use App\Repository\ParticipationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Form\GroupeType;
use App\Entity\MembreGroupe;
use App\Repository\UserRepository;
use App\Form\ParticipationType;
use App\Entity\LivrableChallenge;
use App\Repository\LivrableChallengeRepository;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use App\Entity\Favori;
use App\Entity\User;
use Knp\Component\Pager\PaginatorInterface;
use App\Service\GithubService;
use App\Service\NotificationChallengeService;


#[Route('/etudiant')]
final class EtudiantController extends AbstractController
{
    private function checkRole(): ?Response
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            return $this->redirectToRoute('app_login');
        }
        if (!in_array($user->getRole(), ['membre', 'etudiant'])) {
            throw $this->createAccessDeniedException('Accès réservé aux membres.');
        }
        return null;
    }

    #[Route('/dashboard', name: 'app_etudiant_dashboard')]
    public function dashboard(): Response
    {
        if ($redirect = $this->checkRole()) return $redirect;
        return $this->render('frontoffice/etudiant/dashboard.html.twig');
    }

    #[Route('/challenges', name: 'app_etudiant_challenges')]
    public function listChallenges(Request $request, ChallengeRepository $challengeRepository, EntityManagerInterface $entityManager, PaginatorInterface $paginator): Response
    {
        if ($redirect = $this->checkRole()) return $redirect;
        $search = $request->query->get('search', '');
        $sortBy = $request->query->get('sort', 'dateDebut');
        $order  = $request->query->get('order', 'DESC');

        $queryBuilder = $challengeRepository->createQueryBuilder('c');

        if ($search) {
            $queryBuilder->where('c.titre LIKE :search OR c.description LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        $validSorts = ['titre', 'dateDebut', 'dateFin'];
        if (in_array($sortBy, $validSorts)) {
            $queryBuilder->orderBy('c.' . $sortBy, $order);
        }

        $challenges = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            6
        );

        // ✅ Utiliser l'utilisateur connecté au lieu de find(1)
        /** @var User|null $user */
        $user = $this->getUser();
        $favorisChallengeIds = [];
        if ($user instanceof User) {
            $favoris = $entityManager->getRepository(Favori::class)->findBy(['user' => $user]);
            $favorisChallengeIds = array_map(function($f) {
                $challenge = $f->getChallenge();
                return $challenge ? $challenge->getId() : null;
            }, $favoris);
            $favorisChallengeIds = array_filter($favorisChallengeIds);
        }

        return $this->render('frontoffice/etudiant/challenges/browse.html.twig', [
            'challenges'          => $challenges,
            'search'              => $search,
            'sortBy'              => $sortBy,
            'order'               => $order,
            'favorisChallengeIds' => $favorisChallengeIds,
        ]);
    }

    #[Route('/challenges/{id}', name: 'app_etudiant_challenge_detail', requirements: ['id' => '\d+'])]
    public function challengeDetail(Challenge $challenge, EntityManagerInterface $entityManager): Response
    {
        if ($redirect = $this->checkRole()) return $redirect;
        /** @var User $user */
        $user = $this->getUser();
        $estFavori = false;
        if ($user) {
            $estFavori = $entityManager->getRepository(Favori::class)->findOneBy([
                'user'      => $user,
                'challenge' => $challenge,
            ]) !== null;
        }

        return $this->render('frontoffice/etudiant/challenges/detail.html.twig', [
            'challenge' => $challenge,
            'estFavori' => $estFavori,
        ]);
    }

    #[Route('/mon-groupe', name: 'app_etudiant_groupe')]
    public function monGroupe(Request $request, GroupeRepository $groupeRepository): Response
    {
        if ($redirect = $this->checkRole()) return $redirect;
        $search = $request->query->get('search', '');
        $sortBy = $request->query->get('sort', 'nomGroupe');
        $order  = $request->query->get('order', 'ASC');

        $queryBuilder = $groupeRepository->createQueryBuilder('g');

        if ($search) {
            $queryBuilder->where('g.nomGroupe LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($sortBy === 'nomGroupe') {
            $queryBuilder->orderBy('g.nomGroupe', $order);
        }

        $groupes = $queryBuilder->getQuery()->getResult();

        return $this->render('frontoffice/etudiant/groupe/index.html.twig', [
            'groupes' => $groupes,
            'search'  => $search,
            'sortBy'  => $sortBy,
            'order'   => $order,
        ]);
    }

    #[Route('/groupe/nouveau', name: 'app_etudiant_groupe_new', methods: ['GET', 'POST'])]
    public function createGroupe(Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($redirect = $this->checkRole()) return $redirect;
        $groupe = new Groupe();
        $form   = $this->createForm(GroupeType::class, $groupe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($groupe);
            $entityManager->flush();
            $this->addFlash('success', 'Groupe créé avec succès ! 🎉');
            return $this->redirectToRoute('app_etudiant_groupe');
        }

        return $this->render('frontoffice/etudiant/groupe/create.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/groupe/{id}', name: 'app_etudiant_groupe_show', requirements: ['id' => '\d+'])]
    public function showGroupe(Groupe $groupe): Response
    {
        if ($redirect = $this->checkRole()) return $redirect;
        return $this->render('frontoffice/etudiant/groupe/show.html.twig', [
            'groupe' => $groupe,
        ]);
    }

    #[Route('/groupe/{id}/modifier', name: 'app_etudiant_groupe_edit', methods: ['GET', 'POST'])]
    public function editGroupe(Request $request, Groupe $groupe, EntityManagerInterface $entityManager): Response
    {
        if ($redirect = $this->checkRole()) return $redirect;
        $form = $this->createForm(GroupeType::class, $groupe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Groupe modifié avec succès ! ✅');
            return $this->redirectToRoute('app_etudiant_groupe_show', ['id' => $groupe->getId()]);
        }

        return $this->render('frontoffice/etudiant/groupe/edit.html.twig', [
            'groupe' => $groupe,
            'form'   => $form,
        ]);
    }

    #[Route('/groupe/{id}/ajouter-membre', name: 'app_etudiant_groupe_add_member', methods: ['GET', 'POST'])]
    public function addMember(Request $request, Groupe $groupe, EntityManagerInterface $entityManager, UserRepository $userRepository): Response
    {
        if ($redirect = $this->checkRole()) return $redirect;
        if ($request->isMethod('POST')) {
            $userId = $request->request->get('user_id');
            $role   = $request->request->get('role', 'membre');

            if (empty($userId)) {
                $this->addFlash('error', 'Veuillez sélectionner un utilisateur. ❌');
                return $this->redirectToRoute('app_etudiant_groupe_add_member', ['id' => $groupe->getId()]);
            }

            $user = $userRepository->find($userId);

            if (!$user) {
                $this->addFlash('error', 'Utilisateur introuvable. ❌');
                return $this->redirectToRoute('app_etudiant_groupe_add_member', ['id' => $groupe->getId()]);
            }

            $existingMemberIds = array_map(
                fn($m) => ($u = $m->getUser()) ? $u->getId() : null,
                $groupe->getMembres()->toArray()
            );
            $existingMemberIds = array_filter($existingMemberIds);
            if (in_array($userId, $existingMemberIds)) {
                $this->addFlash('error', 'Cet utilisateur est déjà membre du groupe ! ❌');
                return $this->redirectToRoute('app_etudiant_groupe_add_member', ['id' => $groupe->getId()]);
            }

            $membre = new MembreGroupe();
            $membre->setUser($user);
            $membre->setGroupe($groupe);
            $membre->setRole(is_string($role) ? $role : 'membre');

            $entityManager->persist($membre);
            $entityManager->flush();

            $this->addFlash('success', 'Membre ajouté avec succès ! 👤');
            return $this->redirectToRoute('app_etudiant_groupe_show', ['id' => $groupe->getId()]);
        }

        $allUsers          = $userRepository->findAll();
        $existingMemberIds = array_map(
            fn($m) => ($u = $m->getUser()) ? $u->getId() : null,
            $groupe->getMembres()->toArray()
        );
        $existingMemberIds = array_filter($existingMemberIds);
        $availableUsers    = array_filter($allUsers, fn($u) => !in_array($u->getId(), $existingMemberIds));

        return $this->render('frontoffice/etudiant/groupe/add_member.html.twig', [
            'groupe'         => $groupe,
            'availableUsers' => $availableUsers,
        ]);
    }

    #[Route('/groupe/{groupeId}/membre/{membreId}/supprimer', name: 'app_etudiant_groupe_remove_member', methods: ['POST'])]
    public function removeMember(int $groupeId, int $membreId, EntityManagerInterface $entityManager, GroupeRepository $groupeRepository): Response
    {
        if ($redirect = $this->checkRole()) return $redirect;
        $membre = $entityManager->getRepository(MembreGroupe::class)->find($membreId);

        $groupe = $membre?->getGroupe();
        if ($membre && $groupe && $groupe->getId() === $groupeId) {
            $entityManager->remove($membre);
            $entityManager->flush();
            $this->addFlash('success', 'Membre retiré du groupe avec succès ! ✅');
        }

        return $this->redirectToRoute('app_etudiant_groupe_show', ['id' => $groupeId]);
    }

    #[Route('/groupe/{id}/supprimer', name: 'app_etudiant_groupe_delete', methods: ['POST'])]
    public function deleteGroupe(Request $request, Groupe $groupe, EntityManagerInterface $entityManager): Response
    {
        if ($redirect = $this->checkRole()) return $redirect;
        if ($this->isCsrfTokenValid('delete' . $groupe->getId(), is_string($token = $request->request->get('_token')) ? $token : null)) {
            if ($groupe->getParticipations()->count() > 0) {
                $this->addFlash('error', 'Impossible de supprimer ce groupe : il a des participations aux challenges.');
                return $this->redirectToRoute('app_etudiant_groupe_show', ['id' => $groupe->getId()]);
            }

            foreach ($groupe->getMembres() as $membre) {
                $entityManager->remove($membre);
            }

            $entityManager->remove($groupe);
            $entityManager->flush();
            $this->addFlash('success', 'Groupe supprimé avec succès ! 🗑️');
        }

        return $this->redirectToRoute('app_etudiant_groupe');
    }

    #[Route('/challenges/{id}/participer', name: 'app_etudiant_challenge_participate', methods: ['GET', 'POST'])]
    public function participateChallenge(Request $request, Challenge $challenge, GroupeRepository $groupeRepository, EntityManagerInterface $entityManager, ParticipationRepository $participationRepository, UserRepository $userRepository, NotificationChallengeService $notifService): Response
    {
        if ($redirect = $this->checkRole()) return $redirect;
        $groupes = $groupeRepository->findAll();

        if ($request->isMethod('POST')) {
            $groupeId = $request->request->get('groupe_id');
            $groupe   = $groupeRepository->find($groupeId);

            if ($groupe) {
                $existingParticipation = $entityManager->createQueryBuilder()
                    ->select('p')
                    ->from(Participation::class, 'p')
                    ->where('p.challenge = :challenge')
                    ->andWhere('p.groupe = :groupe')
                    ->setParameter('challenge', $challenge)
                    ->setParameter('groupe', $groupe)
                    ->getQuery()
                    ->getOneOrNullResult();

                if ($existingParticipation) {
                    $this->addFlash('error', 'Ce groupe participe déjà à ce challenge ! ❌');
                    return $this->redirectToRoute('app_etudiant_challenge_detail', ['id' => $challenge->getId()]);
                }

                $participation = new Participation();
                $participation->setChallenge($challenge);
                $participation->setGroupe($groupe);
                $participation->setDateParticipation(new \DateTime());

                $entityManager->persist($participation);
                $entityManager->flush();

                // Notifier tous les responsables
                $currentUser = $this->getUser();
                if ($currentUser instanceof \App\Entity\User) {
                    $responsables = $userRepository->findBy(['role' => 'responsable_club']);
                    foreach ($responsables as $responsable) {
                        $notifService->notifierParticipation($currentUser, $responsable, $participation);
                }                

                }
                $this->addFlash('success', 'Participation enregistrée avec succès ! 🎉');
                return $this->redirectToRoute('app_etudiant_participations');
            }
        }

        return $this->render('frontoffice/etudiant/challenges/participate.html.twig', [
            'challenge' => $challenge,
            'groupes'   => $groupes,
        ]);
    }

    #[Route('/mes-participations', name: 'app_etudiant_participations')]
    public function mesParticipations(Request $request, ParticipationRepository $participationRepository, PaginatorInterface $paginator): Response
    {
        if ($redirect = $this->checkRole()) return $redirect;
        $search      = $request->query->get('search', '');
        $sortBy      = $request->query->get('sort', 'dateParticipation');
        $order       = $request->query->get('order', 'DESC');

        $queryBuilder = $participationRepository->createQueryBuilder('p')
            ->leftJoin('p.challenge', 'c')
            ->leftJoin('p.groupe', 'g');

        if ($search) {
            $queryBuilder->where('c.titre LIKE :search OR g.nomGroupe LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if (in_array($sortBy, ['dateParticipation'])) {
            $queryBuilder->orderBy('p.' . $sortBy, $order);
        }

        $participations = $paginator->paginate($queryBuilder, $request->query->getInt('page', 1), 5);

        return $this->render('frontoffice/etudiant/participations/index.html.twig', [
            'participations' => $participations,
            'search'         => $search,
            'sortBy'         => $sortBy,
            'order'          => $order,
        ]);
    }

    #[Route('/participation/{id}/supprimer', name: 'app_etudiant_participation_delete', methods: ['POST'])]
    public function deleteParticipation(Request $request, Participation $participation, EntityManagerInterface $entityManager): Response
    {
        if ($redirect = $this->checkRole()) return $redirect;
        if ($this->isCsrfTokenValid('delete' . $participation->getId(), is_string($token = $request->request->get('_token')) ? $token : null)) {
            $entityManager->remove($participation);
            $entityManager->flush();
            $this->addFlash('success', 'Participation supprimée avec succès ! 🗑️');
        }

        return $this->redirectToRoute('app_etudiant_participations');
    }

    #[Route('/participation/{id}/soumettre-livrable', name: 'app_etudiant_livrable_submit', methods: ['GET', 'POST'])]
    public function submitLivrable(Request $request, Participation $participation, EntityManagerInterface $entityManager, GithubService $githubService, UserRepository $userRepository, NotificationChallengeService $notifService): Response
    {
        if ($redirect = $this->checkRole()) return $redirect;
        $existingLivrable = $entityManager->getRepository(LivrableChallenge::class)->findOneBy([
            'groupe'    => $participation->getGroupe(),
            'challenge' => $participation->getChallenge(),
        ]);

        if ($existingLivrable) {
            $this->addFlash('error', 'Vous avez déjà soumis un livrable pour ce challenge ! ❌');
            return $this->redirectToRoute('app_etudiant_participations');
        }

        if ($request->isMethod('POST')) {
            $githubUrl = $request->request->get('githubUrl');
            $file      = $request->files->get('fichier');

            if (empty($githubUrl) && !$file) {
                $this->addFlash('error', 'Veuillez fournir soit un lien GitHub, soit un fichier ZIP. ❌');
                return $this->redirectToRoute('app_etudiant_livrable_submit', ['id' => $participation->getId()]);
            }

            $livrable = new LivrableChallenge();
            $livrable->setDateSoumission(new \DateTimeImmutable());
            $livrable->setGroupe($participation->getGroupe());
            $livrable->setChallenge($participation->getChallenge());
            $livrable->setStatut('en_attente');

            if (!empty($githubUrl) && is_string($githubUrl)) {
                $validation = $githubService->validateRepository($githubUrl);
                if (!$validation['valid']) {
                    $this->addFlash('error', $validation['message']);
                    return $this->redirectToRoute('app_etudiant_livrable_submit', ['id' => $participation->getId()]);
                }
                $livrable->setGithubUrl($githubUrl);
                if (!$file) {
                    $livrable->setFichier('github_' . uniqid());
                }
                $this->addFlash('success', '✅ Repository GitHub validé : ' . $validation['info']['name']);
            }

            if ($file) {
                $newFilename = uniqid() . '.' . $file->guessExtension();
                try {
                    $file->move($this->getParameter('uploads_directory'), $newFilename);
                    $livrable->setFichier($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload du fichier. ❌');
                    return $this->redirectToRoute('app_etudiant_livrable_submit', ['id' => $participation->getId()]);
                }
            }

            $entityManager->persist($livrable);
            $entityManager->flush();

            // Notifier tous les responsables
            $currentUser = $this->getUser();
            if ($currentUser instanceof \App\Entity\User) {
                $responsables = $userRepository->findBy(['role' => 'responsable_club']);
                foreach ($responsables as $responsable) {
                    $notifService->notifierSoumissionLivrable($currentUser, $responsable, $livrable);
                }
            }

            $this->addFlash('success', $githubUrl ? '🎉 Livrable GitHub soumis !' : '🎉 Livrable soumis !');
            return $this->redirectToRoute('app_etudiant_participations');
        }

        return $this->render('frontoffice/etudiant/livrables/submit.html.twig', [
            'participation' => $participation,
        ]);
    }

    #[Route('/mes-livrables', name: 'app_etudiant_livrables')]
public function mesLivrables(Request $request, LivrableChallengeRepository $livrableRepository, PaginatorInterface $paginator): Response
{
    if ($redirect = $this->checkRole()) return $redirect;
    
    $user = $this->getUser(); // ✅ Récupérer l'utilisateur connecté
    if (!$user instanceof \App\Entity\User) {
        throw $this->createAccessDeniedException('Utilisateur non connecté.');
    }
    
    $search = $request->query->get('search', '');
    $sortBy = $request->query->get('sort', 'dateSoumission');
    $order = $request->query->get('order', 'DESC');
    $filterStatut = $request->query->get('statut', '');

    // ✅ Récupérer les groupes de l'étudiant
    $groupes = [];
    foreach ($user->getMembresGroupe() as $membreGroupe) {
        $groupes[] = $membreGroupe->getGroupe();
    }

    $queryBuilder = $livrableRepository->createQueryBuilder('l')
        ->leftJoin('l.challenge', 'c')
        ->leftJoin('l.groupe', 'g')
        ->where('l.groupe IN (:groupes)') // ✅ Filtrer par les groupes de l'étudiant
        ->setParameter('groupes', $groupes);

    if ($search) {
        $queryBuilder->andWhere('c.titre LIKE :search OR g.nomGroupe LIKE :search')
            ->setParameter('search', '%' . $search . '%');
    }

    if ($filterStatut) {
        $queryBuilder->andWhere('l.statut = :statut')->setParameter('statut', $filterStatut);
    }

    if (in_array($sortBy, ['dateSoumission'])) {
        $queryBuilder->orderBy('l.' . $sortBy, $order);
    }

    $livrables = $paginator->paginate($queryBuilder, $request->query->getInt('page', 1), 3);

    return $this->render('frontoffice/etudiant/livrables/index.html.twig', [
        'livrables' => $livrables,
        'search' => $search,
        'sortBy' => $sortBy,
        'order' => $order,
        'filterStatut' => $filterStatut,
        'currentUser' => $user, // ✅ Passer l'utilisateur au template
    ]);
}

#[Route('/livrable/{id}/supprimer', name: 'app_etudiant_livrable_delete', methods: ['POST'])]
public function deleteLivrable(Request $request, LivrableChallenge $livrable, EntityManagerInterface $entityManager, UserRepository $userRepository, NotificationChallengeService $notifService): Response
{
    if ($redirect = $this->checkRole()) return $redirect;
    if ($this->isCsrfTokenValid('delete' . $livrable->getId(), is_string($token = $request->request->get('_token')) ? $token : null)) {
        /** @var string $uploadsDir */
        $uploadsDir = $this->getParameter('uploads_directory');
        $filePath = $uploadsDir . '/' . $livrable->getFichier();
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        $entityManager->remove($livrable);
        $entityManager->flush();
        $this->addFlash('success', 'Livrable supprimé avec succès ! 🗑️');

        $currentUser = $this->getUser();
        $challenge = $livrable->getChallenge();
        if ($currentUser instanceof \App\Entity\User && $challenge) {
            $responsables = $userRepository->findBy(['role' => 'responsable_club']);
            foreach ($responsables as $responsable) {
                $notifService->notifierSuppressionLivrable($currentUser, $responsable, $challenge->getTitre() ?? 'Challenge');
            }
        }
    }

    return $this->redirectToRoute('app_etudiant_livrables');
}

// ✅ NOUVELLE ROUTE : Voir le détail de l'évaluation
#[Route('/livrable/{id}/evaluation', name: 'app_etudiant_livrable_evaluation')]
public function viewEvaluation(LivrableChallenge $livrable): Response
{
    if ($redirect = $this->checkRole()) return $redirect;
    
    $user = $this->getUser();
    
    // Vérifier que l'étudiant fait partie du groupe
    $groupe = $livrable->getGroupe();
    if (!$groupe) {
        throw $this->createNotFoundException('Groupe introuvable.');
    }
    
    $isMembre = false;
    foreach ($groupe->getMembres() as $membreGroupe) {
        if ($membreGroupe->getUser() === $user) {
            $isMembre = true;
            break;
        }
    }
    
    if (!$isMembre) {
        throw $this->createAccessDeniedException('Vous ne faites pas partie de ce groupe.');
    }
    
    return $this->render('frontoffice/etudiant/livrables/evaluation.html.twig', [
        'livrable' => $livrable,
        'currentUser' => $user,
    ]);
}

    #[Route('/challenge/{id}/favori/ajouter', name: 'app_etudiant_favori_add', methods: ['POST'])]
    public function addFavori(Request $request, Challenge $challenge, EntityManagerInterface $entityManager): Response
    {
        if ($redirect = $this->checkRole()) return $redirect;
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $existingFavori = $entityManager->getRepository(Favori::class)->findOneBy([
            'user'      => $user,
            'challenge' => $challenge,
        ]);

        if (!$existingFavori) {
            $favori = new Favori();
            $favori->setUser($user);
            $favori->setChallenge($challenge);
            $entityManager->persist($favori);
            $entityManager->flush();
            $this->addFlash('success', 'Challenge ajouté aux favoris ! ⭐');
        } else {
            $this->addFlash('info', 'Ce challenge est déjà dans vos favoris.');
        }

        return $this->redirectToRoute('app_etudiant_challenges');
    }

    #[Route('/challenge/{id}/favori/retirer', name: 'app_etudiant_favori_remove', methods: ['POST'])]
    public function removeFavori(Request $request, Challenge $challenge, EntityManagerInterface $entityManager): Response
    {
        if ($redirect = $this->checkRole()) return $redirect;
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $favori = $entityManager->getRepository(Favori::class)->findOneBy([
            'user'      => $user,
            'challenge' => $challenge,
        ]);

        if ($favori) {
            $entityManager->remove($favori);
            $entityManager->flush();
            $this->addFlash('success', 'Challenge retiré des favoris.');
        }

        return $this->redirectToRoute('app_etudiant_challenges');
    }

    #[Route('/mes-favoris', name: 'app_etudiant_favoris')]
    public function mesFavoris(EntityManagerInterface $entityManager): Response
    {
        if ($redirect = $this->checkRole()) return $redirect;
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $favoris = $entityManager->getRepository(Favori::class)->findBy(
            ['user' => $user],
            ['dateAjout' => 'DESC']
        );

        return $this->render('frontoffice/etudiant/favoris/index.html.twig', [
            'favoris' => $favoris,
        ]);
    }
}