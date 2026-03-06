<?php

namespace App\EventSubscriber;

use App\Entity\Notification;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Twig\Environment;

class NotificationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private EntityManagerInterface $em,
        private Environment $twig
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }

    public function onKernelController(ControllerEvent $event): void
{
    if (!$event->isMainRequest()) return;

    $token = $this->tokenStorage->getToken();
    if (!$token) return;

    $user = $token->getUser();
    if (!$user instanceof User) return;

    $notifs = $this->em->getRepository(Notification::class)->findBy(
        ['destinataire' => $user, 'isLu' => false],
        ['createdAt' => 'DESC']
    );

    $this->twig->addGlobal('notificationsNonLues', $notifs);
    $this->twig->addGlobal('currentUser', $user);  // ← _user sera résolu
    $this->twig->addGlobal('navUser', $user);       // ← fallback
}
}