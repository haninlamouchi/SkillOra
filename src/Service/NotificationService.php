<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class NotificationService
{
    private $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    public function envoyerEmail($to, $sujet, $message)
    {
        dump("EMAIL ENVOYÉ À : ".$to);

        $email = (new Email())
            ->from('noreply@skillora.tn')
            ->to($to)
            ->subject($sujet)
            ->text($message);

        $this->mailer->send($email);
    }
}
