<?php

namespace App\Service;

use App\Entity\Notification;
use App\Entity\User;
use App\Entity\Publication;
use App\Entity\Commentaire;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\RouterInterface;

class NotificationService
{
    public function __construct(
        private EntityManagerInterface $em,
        private HubInterface $hub,
        private RouterInterface $router
    ) {}

    /**
     * Etudiant commente une publication du responsable
     * → Le RESPONSABLE (propriétaire de la pub) reçoit la notif
     * Lien : vers la page de supervision des commentaires du responsable
     */
    public function notifierCommentaire(
        User $etudiant,
        User $responsable,
        Publication $publication,
        Commentaire $commentaire
    ): void {
        // Lien vers la supervision commentaires du responsable
        $lien = $this->router->generate('app_responsable_club_commentaire_index', [
            'userId' => $responsable->getId(),
        ]);

        $notif = new Notification();
        $notif->setMessage(
            "💬 " . $etudiant->getPrenom() . " " . $etudiant->getNom() .
            " (Étudiant) a commenté votre publication : \"" . $publication->getTitre() . "\""
        );
        $notif->setType('commentaire');
        $notif->setExpediteur($etudiant);
        $notif->setDestinataire($responsable);
        $notif->setPublication($publication);
        $notif->setLienRedirection($lien);
        $notif->setRoleExpediteur($etudiant->getRole());

        $this->em->persist($notif);
        $this->em->flush();

        $this->push($notif);
    }

    /**
     * Responsable répond à un commentaire d'un étudiant
     * → L'ETUDIANT auteur du commentaire reçoit la notif
     * Lien : vers la publication du forum pour voir la réponse
     */
    public function notifierReponse(
        User $responsable,
        User $etudiant,
        Publication $publication
    ): void {
        // Lien vers la publication dans le forum pour que l'étudiant voie la réponse
        $lien = $this->router->generate('app_forum_show', [
            'id' => $publication->getId(),
        ]) . '?u=' . $etudiant->getId();

        $notif = new Notification();
        $notif->setMessage(
            "↩️ " . $responsable->getPrenom() . " " . $responsable->getNom() .
            " (Responsable Club) a répondu à votre commentaire sur \"" . $publication->getTitre() . "\""
        );
        $notif->setType('reponse');
        $notif->setExpediteur($responsable);
        $notif->setDestinataire($etudiant);
        $notif->setPublication($publication);
        $notif->setLienRedirection($lien);
        $notif->setRoleExpediteur($responsable->getRole());

        $this->em->persist($notif);
        $this->em->flush();

        $this->push($notif);
    }

    /**
     * Push Mercure en temps réel
     */
    private function push(Notification $notif): void
    {
        $update = new Update(
            "/notifications/user/" . $notif->getDestinataire()->getId(),
            json_encode([
                'id'             => $notif->getId(),
                'message'        => $notif->getMessage(),
                'type'           => $notif->getType(),
                'createdAt'      => $notif->getCreatedAt()->format('d/m/Y H:i'),
                'lien'           => $notif->getLienRedirection(),
                'roleExpediteur' => $notif->getRoleExpediteur(),
                'pubId'          => $notif->getPublication()?->getId(),
            ])
        );
        $this->hub->publish($update);
    }

    /**
     * Notifications non lues d'un utilisateur
     */
    public function getNonLues(User $user): array
    {
        return $this->em->getRepository(Notification::class)
            ->findBy(['destinataire' => $user, 'isLu' => false], ['createdAt' => 'DESC']);
    }

    /**
     * Marquer toutes les notifs d'un user comme lues
     */
    public function marquerToutLu(int $userId): void
    {
        $notifs = $this->em->getRepository(Notification::class)
            ->findBy(['destinataire' => $userId, 'isLu' => false]);
        foreach ($notifs as $n) {
            $n->setIsLu(true);
        }
        $this->em->flush();
    }

    /**
     * Notifier un étudiant qu'une publication a été validée par un responsable
     */
    public function notifierValidation(User $responsable, User $etudiant, Publication $publication): void
{
    $lien = $this->router->generate('app_forum_show', [
        'id' => $publication->getId(),
    ]) . '?u=' . $etudiant->getId();

    $notif = new Notification();
    $notif->setMessage(
        "✅ Votre publication \"" . $publication->getTitre() . 
        "\" a été validée et publiée par " . 
        $responsable->getPrenom() . " " . $responsable->getNom() . " !"
    );
    $notif->setType('validation');
    $notif->setExpediteur($responsable);
    $notif->setDestinataire($etudiant);
    $notif->setPublication($publication);
    $notif->setLienRedirection($lien);
    $notif->setRoleExpediteur($responsable->getRole());

    $this->em->persist($notif);
    $this->em->flush();

    $this->push($notif);
}}