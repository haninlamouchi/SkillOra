<?php

namespace App\EventSubscriber;

use App\Entity\NotificationClub;
use App\Event\DemandeClubEvent;
use App\Repository\UserRepository;
use App\Service\SmsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class DemandeClubSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private MailerInterface $mailer,
        private EntityManagerInterface $em,
        private UserRepository $userRepository,
        private SmsService $smsService
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [DemandeClubEvent::NAME => 'onDemandeClub'];
    }

    public function onDemandeClub(DemandeClubEvent $event): void
    {
        $demande = $event->getDemande();
        $user    = $demande->getResponsable();
        $admin   = $this->userRepository->findOneBy(['role' => 'admin']);

        if (!$user || !$admin) {
            return;
        }

        // 1️⃣ Email à l'admin
        $adminEmail = $admin->getEmail();
        if (!$adminEmail) {
            return;
        }

        $email = (new Email())
            ->from('malekfathidabbek@gmail.com')
            ->to($adminEmail)
            ->subject('🏫 Nouvelle demande de création de club')
            ->html('
                <h3>Bonjour Admin,</h3>
                <p>
                    <strong>' . $user->getNom() . '</strong>
                    souhaite créer le club :
                    <strong>' . $demande->getNom() . '</strong>.
                </p>
                <p>Connectez-vous pour accepter ou refuser cette demande.</p>
            ');
        $this->mailer->send($email);

        

        // 3️⃣ Notification en BDD
        $notification = new NotificationClub();
        $notification->setMessage($user->getNom() . ' souhaite créer le club ' . $demande->getNom());
        $notification->setIsRead(false);
        $notification->setCreatedAt(new \DateTimeImmutable());
        $notification->setType('creation_club');
        $notification->setLienRedirection('/admin/club/demandes');
        $notification->setDestinataire($admin);
        $notification->setExpediteur($user);

        $this->em->persist($notification);
        $this->em->flush();
    }
}