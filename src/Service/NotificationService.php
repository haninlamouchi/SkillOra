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
        dump("Email envoyé à $to | Sujet: $sujet | Message: $message");
        $email = (new Email())
            ->from('noreply@challenge.com')
            ->to($to)
            ->subject($sujet)
            ->text($message);

        $this->mailer->send($email);
    }
}
