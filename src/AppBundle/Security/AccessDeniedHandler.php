<?php

namespace AppBundle\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;
use Twig\Environment;

class AccessDeniedHandler implements AccessDeniedHandlerInterface
{
    private $twig;
    private $debug;

    // On récupère le paramètre debug ici
    public function __construct(Environment $twig, $debug)
    {
        $this->twig = $twig;
        $this->debug = $debug;
    }

    public function handle(Request $request, AccessDeniedException $accessDeniedException)
    {
        // SI ON EST EN MODE DEV/DEBUG : On ne fait rien !
        // En retournant null, on dit à Symfony de continuer son comportement par défaut (afficher la page orange)
        if ($this->debug) {
            return null;
        }

        // SI ON EST EN PROD : On affiche ta page d'erreur personnalisée
        $content = $this->twig->render('@Twig/Exception/error403.html.twig', [
            'status_code' => 403,
            'status_text' => 'Forbidden',
        ]);

        return new Response($content, 403);
    }
}
