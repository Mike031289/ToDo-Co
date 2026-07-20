<?php

namespace AppBundle\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;
use Twig\Environment;

class AccessDeniedHandler implements AccessDeniedHandlerInterface
{
    /**
     * @var Environment
     */
    private $twig;

    /**
     * @var bool
     */
    private $debug;

    /**
     * AccessDeniedHandler constructor.
     *
     * @param Environment $twig
     * @param bool        $debug
     */
    public function __construct(Environment $twig, $debug)
    {
        $this->twig = $twig;
        $this->debug = $debug;
    }

    /**
     * {@inheritdoc}
     *
     * @param Request $request The request (unused due to interface contract)
     * @param AccessDeniedException $accessDeniedException The execution exception
     *
     * @return Response|null
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function handle(Request $request, AccessDeniedException $accessDeniedException)
    {
        // Strict type comparison to comply with clean code standards
        if ($this->debug === true) {
            return null;
        }

        // Render the custom 403 corporate identity error page for production environment
        $content = $this->twig->render('@Twig/Exception/error403.html.twig', [
            'status_code' => 403,
            'status_text' => 'Forbidden',
        ]);

        return new Response($content, 403);
    }
}
