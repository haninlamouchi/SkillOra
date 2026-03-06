<?php

namespace App\EventSubscriber;

use App\Entity\NotificationClub;
use App\Event\DemandeMembreEvent;
use App\Service\SmsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class DemandeMembreSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private MailerInterface $mailer,
        private EntityManagerInterface $em,
        private SmsService $smsService
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [DemandeMembreEvent::NAME => 'onDemandeMembre'];
    }

   public function onDemandeMembre(DemandeMembreEvent $event): void
{
   
    $demande     = $event->getDemande();
    $club        = $demande->getClub();
    $responsable = $club?->getResponsable();
    $membre      = $demande->getUser();

    if (!$responsable || !$membre || !$club) {
        return;
    }

    // ✅ Email au responsable (demande reçue)
    $responsableEmail = $responsable->getEmail();
    if (!$responsableEmail) {
        return;
    }

    $email = (new Email())
        ->from('malekfathidabbek@gmail.com')
        ->to($responsableEmail)
        ->subject('📩 Nouvelle demande d\'adhésion')
        ->html('
            <h3>Bonjour ' . $responsable->getNom() . ',</h3>
            <p>
                <strong>' . $membre->getNom() . '</strong>
                souhaite rejoindre votre club
                <strong>' . $club->getNom() . '</strong>.
            </p>
            <p>Connectez-vous pour accepter ou refuser la demande.</p>
        ');
    $this->mailer->send($email);


    // ✅ Notification en BDD
    $notification = new NotificationClub();
    $notification->setMessage($membre->getNom() . ' souhaite rejoindre le club ' . $club->getNom());
    $notification->setIsRead(false);
    $notification->setCreatedAt(new \DateTimeImmutable());
    $notification->setType('adhesion');
    $notification->setLienRedirection('/responsable/club/adhesions');
    $notification->setDestinataire($responsable);
    $notification->setExpediteur($membre);

    $this->em->persist($notification);
    $this->em->flush();
    
     
}}