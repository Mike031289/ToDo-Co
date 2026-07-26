<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * Class SecurityController
 *
 * Handles user authentication actions (login and logout).
 *
 * @package App\Controller
 */
class SecurityController extends AbstractController
{
    /**
     * Renders the user login form and retrieves authentication errors.
     *
     * @Route("/login", name="login")
     *
     * @param AuthenticationUtils $authenticationUtils Injected via Dependency Injection
     * @return Response
     */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // Last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error'         => $error,
        ]);
    }

    /**
     * Logout action stub (intercepted by the Symfony Security firewall).
     *
     * @Route("/logout", name="logout")
     *
     * @return void
     */
    public function logout(): void
    {
        // This method can be blank: it will be intercepted by the logout key in security.yaml
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
