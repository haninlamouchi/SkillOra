<?php

namespace App\Service;

use App\Entity\Notification;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class NotificationServiceUser
{
    public function __construct(private EntityManagerInterface $em) {}

    public function notifyNewUser(User $user): void
    {
        $notif = new Notification();
        $notif->setMessage('New user registered: ' . $user->getPrenom() . ' ' . $user->getNom() . ' (' . $user->getEmail() . ')');
        $notif->setType('new_user');
        $notif->setExpediteur($user);
        $notif->setLienRedirection('/backoffice/user/' . $user->getId());
        $notif->setRoleExpediteur($user->getRole());
        $this->em->persist($notif);
        $this->em->flush();
    }
}
