<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(private RouterInterface $router) {}

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): RedirectResponse
    {
        $user = $token->getUser();

        // ✅ Si admin → redirection directe vers le backoffice
        if ($user->getRole() === 'admin') {
            return new RedirectResponse(
                $this->router->generate('app_admin_dashboard')
            );
        }

        // Sinon → page d'accueil avec userId
        return new RedirectResponse(
            $this->router->generate('front_home_user', ['userId' => $user->getId()])
        );
    }
}