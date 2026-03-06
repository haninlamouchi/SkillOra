<?php

namespace App\Twig;

use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private UserRepository $userRepository,
    ) {}

    public function getFilters(): array
    {
        return [
            new TwigFilter('json_decode', [$this, 'jsonDecode']),
        ];
    }

    public function jsonDecode(?string $jsonString): mixed
    {
        if (!$jsonString) {
            return null;
        }
        
        return json_decode($jsonString, true);
    }

    public function getGlobals(): array
    {
        $navUser = null;
        $request = $this->requestStack->getCurrentRequest();

        if ($request) {
            $userId = $request->attributes->get('userId');

            if (!$userId) {
                $userId = $request->query->get('u');
            }

            if (!$userId) {
                $userId = $request->request->get('u');
            }

            if (!$userId) {
                $session = $request->getSession();
                $userId = $session->get('_nav_user_id');
            }

            if ($userId) {
                $navUser = $this->userRepository->find((int) $userId);

                // Si l'ID fourni n'existe pas en base, on nettoie la session
                if (!$navUser && $request->hasSession()) {
                    $request->getSession()->remove('_nav_user_id');
                }

                if ($navUser && $request->hasSession()) {
                    $request->getSession()->set('_nav_user_id', $navUser->getId());
                }
            }
        }

        return [
            'navUser' => $navUser,
        ];
    }
}