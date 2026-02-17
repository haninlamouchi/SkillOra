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


#[Route('/etudiant')]
final class EtudiantController extends AbstractController
{
    #[Route('/dashboard', name: 'app_etudiant_dashboard')]
    public function dashboard(): Response
    {
        return $this->render('frontoffice/etudiant/dashboard.html.twig');
    }

    #[Route('/challenges', name: 'app_etudiant_challenges')]
    public function listChallenges(Request $request, ChallengeRepository $challengeRepository, EntityManagerInterface $entityManager): Response
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

        // Récupérer les favoris de l'utilisateur
        $user = $entityManager->getRepository(User::class)->find(1);
        $favoris = $entityManager->getRepository(Favori::class)->findBy(['user' => $user]);
        $favorisChallengeIds = array_map(fn($f) => $f->getChallenge()->getId(), $favoris);

        return $this->render('frontoffice/etudiant/challenges/browse.html.twig', [
            'challenges' => $challenges,
            'search' => $search,
            'sortBy' => $sortBy,
            'order' => $order,
            'favorisChallengeIds' => $favorisChallengeIds,
        ]);
    }

    #[Route('/challenges/{id}', name: 'app_etudiant_challenge_detail', requirements: ['id' => '\d+'])]
    public function challengeDetail(Challenge $challenge, EntityManagerInterface $entityManager): Response
    {
        $user = $entityManager->getRepository(User::class)->find(1);
        $estFavori = $entityManager->getRepository(Favori::class)->findOneBy([
            'user' => $user,
            'challenge' => $challenge,
        ]) !== null;
        return $this->render('frontoffice/etudiant/challenges/detail.html.twig', [
            'challenge' => $challenge,
            'estFavori' => $estFavori,
        ]);
    }

    #[Route('/mon-groupe', name: 'app_etudiant_groupe')]
public function monGroupe(Request $request, GroupeRepository $groupeRepository): Response
{
    $search = $request->query->get('search', '');
    $sortBy = $request->query->get('sort', 'nomGroupe');
    $order = $request->query->get('order', 'ASC');
    
    $queryBuilder = $groupeRepository->createQueryBuilder('g');
    
    // Recherche
    if ($search) {
        $queryBuilder->where('g.nomGroupe LIKE :search')
            ->setParameter('search', '%' . $search . '%');
    }
    
    // Tri
    if ($sortBy === 'nomGroupe') {
        $queryBuilder->orderBy('g.nomGroupe', $order);
    }
    
    $groupes = $queryBuilder->getQuery()->getResult();
    
    return $this->render('frontoffice/etudiant/groupe/index.html.twig', [
        'groupes' => $groupes,
        'search' => $search,
        'sortBy' => $sortBy,
        'order' => $order,
    ]);
}

    #[Route('/groupe/nouveau', name: 'app_etudiant_groupe_new', methods: ['GET', 'POST'])]
    public function createGroupe(Request $request, EntityManagerInterface $entityManager): Response
{
    $groupe = new Groupe();
    $form = $this->createForm(GroupeType::class, $groupe);
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
    return $this->render('frontoffice/etudiant/groupe/show.html.twig', [
        'groupe' => $groupe,
    ]);
}

#[Route('/groupe/{id}/modifier', name: 'app_etudiant_groupe_edit', methods: ['GET', 'POST'])]
public function editGroupe(Request $request, Groupe $groupe, EntityManagerInterface $entityManager): Response
{
    $form = $this->createForm(GroupeType::class, $groupe);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $entityManager->flush();
        $this->addFlash('success', 'Groupe modifié avec succès ! ✅');
        return $this->redirectToRoute('app_etudiant_groupe_show', ['id' => $groupe->getId()]);
    }

    return $this->render('frontoffice/etudiant/groupe/edit.html.twig', [
        'groupe' => $groupe,
        'form' => $form,
    ]);
}

#[Route('/groupe/{id}/ajouter-membre', name: 'app_etudiant_groupe_add_member', methods: ['GET', 'POST'])]
public function addMember(Request $request, Groupe $groupe, EntityManagerInterface $entityManager, UserRepository $userRepository): Response
{
    if ($request->isMethod('POST')) {
    $userId = $request->request->get('user_id');
    $role = $request->request->get('role', 'membre');
    
    // Validation côté serveur
    if (empty($userId)) {
        $this->addFlash('error', 'Veuillez sélectionner un utilisateur. ❌');
        return $this->redirectToRoute('app_etudiant_groupe_add_member', ['id' => $groupe->getId()]);
    }
    
    $user = $userRepository->find($userId);
    
    if (!$user) {
        $this->addFlash('error', 'Utilisateur introuvable. ❌');
        return $this->redirectToRoute('app_etudiant_groupe_add_member', ['id' => $groupe->getId()]);
    }
    
    // Vérifier si l'utilisateur n'est pas déjà membre
    $existingMemberIds = array_map(fn($m) => $m->getUser()->getId(), $groupe->getMembres()->toArray());
    if (in_array($userId, $existingMemberIds)) {
        $this->addFlash('error', 'Cet utilisateur est déjà membre du groupe ! ❌');
        return $this->redirectToRoute('app_etudiant_groupe_add_member', ['id' => $groupe->getId()]);
    }
    
    $membre = new MembreGroupe();
    $membre->setUser($user);
    $membre->setGroupe($groupe);
    $membre->setRole($role);
    
    $entityManager->persist($membre);
    $entityManager->flush();
    
    $this->addFlash('success', 'Membre ajouté avec succès ! 👤');
    
    return $this->redirectToRoute('app_etudiant_groupe_show', ['id' => $groupe->getId()]);
}
    
    // Récupérer tous les utilisateurs qui ne sont pas déjà dans le groupe
    $allUsers = $userRepository->findAll();
    $existingMemberIds = array_map(fn($m) => $m->getUser()->getId(), $groupe->getMembres()->toArray());
    $availableUsers = array_filter($allUsers, fn($u) => !in_array($u->getId(), $existingMemberIds));
    
    return $this->render('frontoffice/etudiant/groupe/add_member.html.twig', [
        'groupe' => $groupe,
        'availableUsers' => $availableUsers,
    ]);
}

#[Route('/groupe/{groupeId}/membre/{membreId}/supprimer', name: 'app_etudiant_groupe_remove_member', methods: ['POST'])]
public function removeMember(int $groupeId, int $membreId, EntityManagerInterface $entityManager, GroupeRepository $groupeRepository): Response
{
    $groupe = $groupeRepository->find($groupeId);
    $membre = $entityManager->getRepository(MembreGroupe::class)->find($membreId);
    
    if ($membre && $membre->getGroupe()->getId() === $groupeId) {
        $entityManager->remove($membre);
        $entityManager->flush();
        
        $this->addFlash('success', 'Membre retiré du groupe avec succès ! ✅');
    }
    
    return $this->redirectToRoute('app_etudiant_groupe_show', ['id' => $groupeId]);
}

#[Route('/groupe/{id}/supprimer', name: 'app_etudiant_groupe_delete', methods: ['POST'])]
public function deleteGroupe(Request $request, Groupe $groupe, EntityManagerInterface $entityManager): Response
{
    if ($this->isCsrfTokenValid('delete'.$groupe->getId(), $request->request->get('_token'))) {
        
        // Vérifier si le groupe a des participations
        if ($groupe->getParticipations()->count() > 0) {
            $this->addFlash('error', 'Impossible de supprimer ce groupe : il a des participations aux challenges. Supprimez d\'abord les participations.');
            return $this->redirectToRoute('app_etudiant_groupe_show', ['id' => $groupe->getId()]);
        }
        
        // Supprimer toutes les relations MembreGroupe
        foreach ($groupe->getMembres() as $membre) {
            $entityManager->remove($membre);
        }
        
        // Supprimer le groupe
        $entityManager->remove($groupe);
        $entityManager->flush();
        
        $this->addFlash('success', 'Groupe supprimé avec succès ! 🗑️');
    }
    
    return $this->redirectToRoute('app_etudiant_groupe');
}

#[Route('/challenges/{id}/participer', name: 'app_etudiant_challenge_participate', methods: ['GET', 'POST'])]
public function participateChallenge(Request $request, Challenge $challenge, GroupeRepository $groupeRepository, EntityManagerInterface $entityManager, ParticipationRepository $participationRepository): Response
{
    // Récupérer tous les groupes disponibles
    $groupes = $groupeRepository->findAll();
    
    if ($request->isMethod('POST')) {
        $groupeId = $request->request->get('groupe_id');
        $groupe = $groupeRepository->find($groupeId);
        
        if ($groupe) {
            // Vérifier si le groupe ne participe pas déjà (VERSION CORRIGÉE)
            $existingParticipation = $participationRepository->findOneBy([
                'challenge' => $challenge->getId(),
                'groupe' => $groupe->getId(),
            ]);
            
            // Alternative encore plus sûre :
            $qb = $entityManager->createQueryBuilder();
            $existingParticipation = $qb->select('p')
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
            
            // Créer la participation
            $participation = new Participation();
            $participation->setChallenge($challenge);
            $participation->setGroupe($groupe);
            $participation->setDateParticipation(new \DateTime());
            
            $entityManager->persist($participation);
            $entityManager->flush();
            
            $this->addFlash('success', 'Participation enregistrée avec succès ! 🎉 Bon courage pour le challenge !');
            return $this->redirectToRoute('app_etudiant_participations');
        }
    }
    
    return $this->render('frontoffice/etudiant/challenges/participate.html.twig', [
        'challenge' => $challenge,
        'groupes' => $groupes,
    ]);
}

#[Route('/mes-participations', name: 'app_etudiant_participations')]
public function mesParticipations(Request $request, ParticipationRepository $participationRepository): Response
{
    $search = $request->query->get('search', '');
    $sortBy = $request->query->get('sort', 'dateParticipation');
    $order = $request->query->get('order', 'DESC');
    
    $queryBuilder = $participationRepository->createQueryBuilder('p')
        ->leftJoin('p.challenge', 'c')
        ->leftJoin('p.groupe', 'g');
    
    // Recherche
    if ($search) {
        $queryBuilder->where('c.titre LIKE :search OR g.nomGroupe LIKE :search')
            ->setParameter('search', '%' . $search . '%');
    }
    
    // Tri
    $validSorts = ['dateParticipation'];
    if (in_array($sortBy, $validSorts)) {
        $queryBuilder->orderBy('p.' . $sortBy, $order);
    }
    
    $participations = $queryBuilder->getQuery()->getResult();
    
    return $this->render('frontoffice/etudiant/participations/index.html.twig', [
        'participations' => $participations,
        'search' => $search,
        'sortBy' => $sortBy,
        'order' => $order,
    ]);
}
#[Route('/participation/{id}/supprimer', name: 'app_etudiant_participation_delete', methods: ['POST'])]
public function deleteParticipation(Request $request, Participation $participation, EntityManagerInterface $entityManager): Response
{
    if ($this->isCsrfTokenValid('delete'.$participation->getId(), $request->request->get('_token'))) {
        
        $challengeTitre = $participation->getChallenge()->getTitre();
        
        $entityManager->remove($participation);
        $entityManager->flush();
        
        $this->addFlash('success', 'Participation au challenge "' . $challengeTitre . '" supprimée avec succès ! 🗑️');
    }
    
    return $this->redirectToRoute('app_etudiant_participations');
}

#[Route('/participation/{id}/soumettre-livrable', name: 'app_etudiant_livrable_submit', methods: ['GET', 'POST'])]
public function submitLivrable(Request $request, Participation $participation, EntityManagerInterface $entityManager): Response
{
    // Vérifier si un livrable existe déjà
    $existingLivrable = $entityManager->getRepository(LivrableChallenge::class)
        ->findOneBy([
            'groupe' => $participation->getGroupe(),
            'challenge' => $participation->getChallenge(),
        ]);
    
    if ($existingLivrable) {
        $this->addFlash('error', 'Vous avez déjà soumis un livrable pour ce challenge ! ❌');
        return $this->redirectToRoute('app_etudiant_participations');
    }
    
    if ($request->isMethod('POST')) {
        $file = $request->files->get('fichier');
        
        if ($file) {
            $newFilename = uniqid().'.'.$file->guessExtension();
            
            try {
                $file->move(
                    $this->getParameter('uploads_directory'),
                    $newFilename
                );
                
                $livrable = new LivrableChallenge();
                $livrable->setFichier($newFilename);
                $livrable->setDateSoumission(new \DateTimeImmutable());
                $livrable->setGroupe($participation->getGroupe());
                $livrable->setChallenge($participation->getChallenge());
                
                $entityManager->persist($livrable);
                $entityManager->flush();
                
                $this->addFlash('success', 'Livrable soumis avec succès ! 🎉 Bonne chance !');
                return $this->redirectToRoute('app_etudiant_participations');
                
            } catch (FileException $e) {
                $this->addFlash('error', 'Erreur lors de l\'upload du fichier.');
            }
        } else {
            $this->addFlash('error', 'Veuillez sélectionner un fichier.');
        }
    }
    
    return $this->render('frontoffice/etudiant/livrables/submit.html.twig', [
        'participation' => $participation,
    ]);
}

#[Route('/mes-livrables', name: 'app_etudiant_livrables')]
public function mesLivrables(Request $request, LivrableChallengeRepository $livrableRepository): Response
{
    $search = $request->query->get('search', '');
    $sortBy = $request->query->get('sort', 'dateSoumission');
    $order = $request->query->get('order', 'DESC');
    $filterStatut = $request->query->get('statut', '');
    
    $queryBuilder = $livrableRepository->createQueryBuilder('l')
        ->leftJoin('l.challenge', 'c')
        ->leftJoin('l.groupe', 'g');
    
    // Recherche
    if ($search) {
        $queryBuilder->where('c.titre LIKE :search OR g.nomGroupe LIKE :search')
            ->setParameter('search', '%' . $search . '%');
    }
    
    // Filtre par statut
    if ($filterStatut) {
        $queryBuilder->andWhere('l.statut = :statut')
            ->setParameter('statut', $filterStatut);
    }
    
    // Tri
    $validSorts = ['dateSoumission'];
    if (in_array($sortBy, $validSorts)) {
        $queryBuilder->orderBy('l.' . $sortBy, $order);
    }
    
    $livrables = $queryBuilder->getQuery()->getResult();
    
    return $this->render('frontoffice/etudiant/livrables/index.html.twig', [
        'livrables' => $livrables,
        'search' => $search,
        'sortBy' => $sortBy,
        'order' => $order,
        'filterStatut' => $filterStatut,
    ]);
}

#[Route('/livrable/{id}/supprimer', name: 'app_etudiant_livrable_delete', methods: ['POST'])]
public function deleteLivrable(Request $request, LivrableChallenge $livrable, EntityManagerInterface $entityManager): Response
{
    if ($this->isCsrfTokenValid('delete'.$livrable->getId(), $request->request->get('_token'))) {
        
        $challengeTitre = $livrable->getChallenge()->getTitre();
        
        // Supprimer le fichier physique du serveur
        $filePath = $this->getParameter('uploads_directory') . '/' . $livrable->getFichier();
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        $entityManager->remove($livrable);
        $entityManager->flush();
        
        $this->addFlash('success', 'Livrable du challenge "' . $challengeTitre . '" supprimé avec succès ! 🗑️');
    }
    
    return $this->redirectToRoute('app_etudiant_livrables');
}

#[Route('/challenge/{id}/favori/ajouter', name: 'app_etudiant_favori_add', methods: ['POST'])]
public function addFavori(Request $request, Challenge $challenge, EntityManagerInterface $entityManager): Response
{
    // Pour l'instant, on simule l'utilisateur connecté avec le premier user
    // Plus tard, ce sera l'utilisateur réellement connecté
    $user = $entityManager->getRepository(User::class)->find(1);
    
    // Vérifier si déjà en favori
    $existingFavori = $entityManager->getRepository(Favori::class)->findOneBy([
        'user' => $user,
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
    $user = $entityManager->getRepository(User::class)->find(1);
    
    $favori = $entityManager->getRepository(Favori::class)->findOneBy([
        'user' => $user,
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
public function mesFavoris(Request $request, EntityManagerInterface $entityManager): Response
{
    $user = $entityManager->getRepository(User::class)->find(1);
    
    $favoris = $entityManager->getRepository(Favori::class)->findBy(
        ['user' => $user],
        ['dateAjout' => 'DESC']
    );
    
    return $this->render('frontoffice/etudiant/favoris/index.html.twig', [
        'favoris' => $favoris,
    ]);
}
}