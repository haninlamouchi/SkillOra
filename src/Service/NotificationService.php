<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mime\Address;

class NotificationService
{
    private $mailer;
    private $logger;

    public function __construct(MailerInterface $mailer, LoggerInterface $logger)
    {
        $this->mailer = $mailer;
        $this->logger = $logger;
    }

    public function envoyerEmail(string $to, string $sujet, string $message): bool
{
    try {
        $email = (new Email())
            ->from(new Address('kmaryem50@gmail.com', 'SkillOra Platform'))
            ->to($to)
            ->subject($sujet)
            ->text($message);

        $this->mailer->send($email);
        return true;
        
    } catch (\Exception $e) {
        $this->logger->error('Erreur envoi email: ' . $e->getMessage());
        return false;
    }
}

    public function envoyerEmailNouveauChallenge(string $to, string $nomEtudiant, string $titreChallenge, string $dateDebut, string $dateFin): bool
    {
        $sujet = "Nouveau Challenge disponible : " . $titreChallenge;
        
        $message = "Bonjour " . $nomEtudiant . ",\n\n"
            . "Un nouveau challenge vient d'etre cree !\n\n"
            . "Challenge : " . $titreChallenge . "\n"
            . "Date debut : " . $dateDebut . "\n"
            . "Date fin : " . $dateFin . "\n\n"
            . "Connectez-vous pour participer !\n\n"
            . "L'equipe SkillOra";
        
        return $this->envoyerEmail($to, $sujet, $message);
    }
}