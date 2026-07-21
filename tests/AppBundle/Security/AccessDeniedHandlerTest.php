<?php

namespace Tests\AppBundle\Security;

use AppBundle\Security\AccessDeniedHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Twig\Environment;

/**
 * Class AccessDeniedHandlerTest
 *
 * Verifies custom 403 response rendering logic based on corporate templates
 * under both debug environments and production configurations.
 *
 * @package Tests\AppBundle\Security
 */
class AccessDeniedHandlerTest extends TestCase
{
    /**
     * @var Environment|\PHPUnit\Framework\MockObject\MockObject
     */
    private $twigMock;

    /**
     * @var Request|\PHPUnit\Framework\MockObject\MockObject
     */
    private $requestMock;

    /**
     * @var AccessDeniedException|\PHPUnit\Framework\MockObject\MockObject
     */
    private $exceptionMock;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->twigMock = $this->createMock(Environment::class);
        $this->requestMock = $this->createMock(Request::class);
        $this->exceptionMock = $this->createMock(AccessDeniedException::class);
    }

    /**
     * Verify that when debug mode is enabled, the handler bypasses execution and returns null.
     *
     * @return void
     */
    public function testHandleReturnsNullInDebugMode()
    {
        $handler = new AccessDeniedHandler($this->twigMock, true);

        $response = $handler->handle($this->requestMock, $this->exceptionMock);

        $this->assertNull($response);
    }

    /**
     * Verify that in production (debug false), a custom 403 Response is returned with standard content.
     *
     * @return void
     */
    public function testHandleReturnsCustomResponseInProductionMode()
    {
        $handler = new AccessDeniedHandler($this->twigMock, false);

        $this->twigMock->expects($this->once())
            ->method('render')
            ->with('@Twig/Exception/error403.html.twig', [
                'status_code' => 403,
                'status_text' => 'Forbidden',
            ])
            ->willReturn('Custom 403 Corporate HTML Content');

        $response = $handler->handle($this->requestMock, $this->exceptionMock);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame('Custom 403 Corporate HTML Content', $response->getContent());
    }
}
