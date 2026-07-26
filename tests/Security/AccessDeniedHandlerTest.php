<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Security\AccessDeniedHandler;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Twig\Environment;

class AccessDeniedHandlerTest extends WebTestCase
{
    /**
     * @var Environment|\PHPUnit\Framework\MockObject\MockObject
     */
    private $twigMock;

    private Request $request;

    /**
     * @var AccessDeniedException|\PHPUnit\Framework\MockObject\MockObject
     */
    private $exceptionMock;

    protected function setUp(): void
    {
        $this->twigMock = $this->createMock(Environment::class);
        $this->request = new Request();
        $this->exceptionMock = $this->createMock(AccessDeniedException::class);
    }

    public function testHandleReturnsNullInDebugMode(): void
    {
        $handler = new AccessDeniedHandler(
            $this->twigMock,
            true
        );

        $response = $handler->handle(
            $this->request,
            $this->exceptionMock
        );

        $this->assertNull($response);
    }

    public function testHandleReturnsCustomResponseInProductionMode(): void
    {
        $handler = new AccessDeniedHandler(
            $this->twigMock,
            false
        );

        $this->twigMock
            ->expects($this->once())
            ->method('render')
            ->with(
                '@Twig/Exception/error403.html.twig',
                [
                    'status_code' => 403,
                    'status_text' => 'Forbidden',
                ]
            )
            ->willReturn('Custom 403 Corporate HTML Content');

        $response = $handler->handle(
            $this->request,
            $this->exceptionMock
        );

        $this->assertInstanceOf(
            Response::class,
            $response
        );

        $this->assertSame(
            403,
            $response->getStatusCode()
        );

        $this->assertSame(
            'Custom 403 Corporate HTML Content',
            $response->getContent()
        );
    }
}
