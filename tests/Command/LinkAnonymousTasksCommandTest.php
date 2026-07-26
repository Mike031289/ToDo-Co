<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\LinkAnonymousTasksCommand;
use App\Entity\Task;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

class LinkAnonymousTasksCommandTest extends WebTestCase
{
    /**
     * Fake password hasher compatible with Symfony 5.4.
     *
     * UserPasswordHasherInterface only declares methods through PHPDoc
     * in Symfony 5.4, so PHPUnit cannot mock hashPassword().
     */
    private function createPasswordHasherFake(): \Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface
    {
        return new class() implements \Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface {
            public function hashPassword(
                PasswordAuthenticatedUserInterface $user,
                string $plainPassword
            ): string {
                return 'hashed_password';
            }

            public function isPasswordValid(
                PasswordAuthenticatedUserInterface $user,
                string $plainPassword
            ): bool {
                return true;
            }

            public function needsRehash(
                PasswordAuthenticatedUserInterface $user
            ): bool {
                return false;
            }
        };
    }


    /**
     * Test the CLI command behavior when the database already contains
     * the anonymous user and no task needs to be linked.
     */
    public function testExecuteCommandWhenSchemaIsClean(): void
    {
        /** @var EntityRepository|MockObject $userRepository */
        $userRepository = $this->createMock(EntityRepository::class);

        $userRepository
            ->method('findOneBy')
            ->willReturn($this->createMock(User::class));


        /** @var EntityRepository|MockObject $taskRepository */
        $taskRepository = $this->createMock(EntityRepository::class);

        $taskRepository
            ->method('findBy')
            ->willReturn([]);


        /** @var EntityManagerInterface|MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $entityManager
            ->method('getRepository')
            ->willReturnMap([
                [User::class, $userRepository],
                [Task::class, $taskRepository],
            ]);


        $command = new LinkAnonymousTasksCommand(
            $entityManager,
            $this->createPasswordHasherFake()
        );


        $commandTester = new CommandTester($command);

        $commandTester->execute([]);


        $this->assertSame(
            0,
            $commandTester->getStatusCode()
        );
    }


    /**
     * Test the CLI command behavior when anonymous tasks exist
     * and must be linked to the anonymous user account.
     */
    public function testExecuteCommandWithAnonymousTasks(): void
    {
        /** @var User|MockObject $userMock */
        $userMock = $this->createMock(User::class);


        /** @var Task|MockObject $taskMock */
        $taskMock = $this->createMock(Task::class);


        /** @var EntityRepository|MockObject $userRepository */
        $userRepository = $this->createMock(EntityRepository::class);

        $userRepository
            ->method('findOneBy')
            ->with([
                'username' => 'anonyme',
            ])
            ->willReturn($userMock);


        /** @var EntityRepository|MockObject $taskRepository */
        $taskRepository = $this->createMock(EntityRepository::class);

        $taskRepository
            ->method('findBy')
            ->with([
                'user' => null,
            ])
            ->willReturn([$taskMock]);


        /** @var EntityManagerInterface|MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $entityManager
            ->method('getRepository')
            ->willReturnMap([
                [User::class, $userRepository],
                [Task::class, $taskRepository],
            ]);


        $taskMock
            ->expects($this->once())
            ->method('setUser')
            ->with($userMock);


        $entityManager
            ->expects($this->once())
            ->method('flush');


        $command = new LinkAnonymousTasksCommand(
            $entityManager,
            $this->createPasswordHasherFake()
        );


        $commandTester = new CommandTester($command);

        $commandTester->execute([]);


        $output = $commandTester->getDisplay();


        $this->assertSame(
            0,
            $commandTester->getStatusCode()
        );


        $this->assertStringContainsString(
            'Linked tasks to the generic anonymous user account successfully',
            $output
        );
    }


    /**
     * Test the CLI command behavior when the anonymous user
     * does not exist and must be created.
     */
    public function testExecuteCommandCreatesVirtualUserWhenMissing(): void
    {
        /** @var Task|MockObject $taskMock */
        $taskMock = $this->createMock(Task::class);


        /** @var EntityRepository|MockObject $userRepository */
        $userRepository = $this->createMock(EntityRepository::class);

        $userRepository
            ->method('findOneBy')
            ->with([
                'username' => 'anonyme',
            ])
            ->willReturn(null);


        /** @var EntityRepository|MockObject $taskRepository */
        $taskRepository = $this->createMock(EntityRepository::class);

        $taskRepository
            ->method('findBy')
            ->with([
                'user' => null,
            ])
            ->willReturn([$taskMock]);


        /** @var EntityManagerInterface|MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $entityManager
            ->method('getRepository')
            ->willReturnCallback(
                function (string $entityName) use (
                    $userRepository,
                    $taskRepository
                ): ?EntityRepository {
                    return match ($entityName) {
                        User::class => $userRepository,
                        Task::class => $taskRepository,
                        default => null,
                    };
                }
            );


        $entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(User::class));


        $entityManager
            ->expects($this->atLeastOnce())
            ->method('flush');


        $command = new LinkAnonymousTasksCommand(
            $entityManager,
            $this->createPasswordHasherFake()
        );


        $commandTester = new CommandTester($command);

        $commandTester->execute([]);


        $output = $commandTester->getDisplay();


        $this->assertSame(
            0,
            $commandTester->getStatusCode()
        );


        $this->assertStringContainsString(
            'Virtual user "anonyme" created successfully.',
            $output
        );
    }
}
