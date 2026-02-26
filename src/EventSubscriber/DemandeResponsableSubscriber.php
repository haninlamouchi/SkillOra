<?php

namespace App\EventSubscriber;

use App\Entity\NotificationClub;
use App\Event\DemandeResponsableEvent;
use App\Repository\UserRepository;
use App\Service\SmsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class DemandeResponsableSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private MailerInterface $mailer,
        private EntityManagerInterface $em,
        private UserRepository $userRepository,
        private SmsService $smsService
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [DemandeResponsableEvent::NAME => 'onDemandeResponsable'];
    }

    public function onDemandeResponsable(DemandeResponsableEvent $event): void
    {
        $demande = $event->getDemande();
        $membre  = $demande->getUser();
        $club    = $demande->getClub();
        $admin   = $this->userRepository->findOneBy(['role' => 'admin']);

        // 1️⃣ Email à l'admin
        $email = (new Email())
            ->from('malekfathidabbek@gmail.com')
            ->to($admin->getEmail())
            ->subject('🔄 Demande de changement de responsable')
            ->html('
                <h3>Bonjour Admin,</h3>
                <p>
                    Le membre <strong>' . $membre->getNom() . '</strong>
                    souhaite devenir responsable du club :
                    <strong>' . $club->getNom() . '</strong>.
                </p>
                <p>Connectez-vous pour accepter ou refuser cette demande.</p>
            ');
        $this->mailer->send($email);

        // 3️⃣ Notification en BDD
        $notification = new NotificationClub();
        $notification->setMessage($membre->getNom() . ' souhaite devenir responsable du club ' . $club->getNom());
        $notification->setIsRead(false);
        $notification->setCreatedAt(new \DateTimeImmutable());
        $notification->setType('responsable');
        $notification->setLienRedirection('/admin/club/adhesions');
        $notification->setDestinataire($admin);
        $notification->setExpediteur($membre);

        $this->em->persist($notification);
        $this->em->flush();
    }
}