<?php

namespace Tests\AppBundle\Command;

use AppBundle\Command\LinkAnonymousTasksCommand;
use AppBundle\Entity\Task;
use AppBundle\Entity\User;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class LinkAnonymousTasksCommandTest extends TestCase
{
    /**
     * Helper to build a Doctrine ManagerRegistry mock that returns our EntityManager mock.
     *
     * @param EntityManagerInterface $entityManager
     * @return ManagerRegistry|MockObject
     */
    private function createDoctrineRegistryMock(EntityManagerInterface $entityManager)
    {
        /** @var ManagerRegistry|MockObject $doctrine */
        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->method('getManager')->willReturn($entityManager);

        return $doctrine;
    }

    /**
     * Test the CLI command behavior when data is already clean.
     */
    public function testExecuteCommandWhenSchemaIsClean()
    {
        $userMock = $this->createMock(User::class);

        /** @var EntityRepository|MockObject $userRepository */
        $userRepository = $this->createMock(EntityRepository::class);
        $userRepository->method('findOneBy')->with(['username' => 'anonyme'])->willReturn($userMock);

        /** @var EntityRepository|MockObject $taskRepository */
        $taskRepository = $this->createMock(EntityRepository::class);
        $taskRepository->method('findBy')->with(['user' => null])->willReturn([]);

        /** @var EntityManagerInterface|MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getRepository')->willReturnMap([
            [User::class, $userRepository],
            [Task::class, $taskRepository],
        ]);

        $doctrineRegistry = $this->createDoctrineRegistryMock($entityManager);

        /** @var ContainerInterface|MockObject $container */
        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->willReturnCallback(function ($serviceName) use ($entityManager, $doctrineRegistry) {
            if ($serviceName === 'doctrine.orm.entity_manager') {
                return $entityManager;
            }
            if ($serviceName === 'doctrine') {
                return $doctrineRegistry;
            }
            return null;
        });

        $command = new LinkAnonymousTasksCommand();
        $command->setContainer($container);

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $output = $commandTester->getDisplay();

        $this->assertSame(0, $commandTester->getStatusCode());
        $this->assertStringContainsString('Data clean-up complete', $output);
    }

    /**
     * Test the CLI command behavior when there are anonymous tasks to link.
     */
    public function testExecuteCommandWithAnonymousTasks()
    {
        $userMock = $this->createMock(User::class);
        $taskMock = $this->createMock(Task::class);

        /** @var EntityRepository|MockObject $userRepository */
        $userRepository = $this->createMock(EntityRepository::class);
        $userRepository->method('findOneBy')->with(['username' => 'anonyme'])->willReturn($userMock);

        /** @var EntityRepository|MockObject $taskRepository */
        $taskRepository = $this->createMock(EntityRepository::class);
        $taskRepository->method('findBy')->with(['user' => null])->willReturn([$taskMock]);

        /** @var EntityManagerInterface|MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getRepository')->willReturnMap([
            [User::class, $userRepository],
            [Task::class, $taskRepository],
        ]);

        $taskMock->expects($this->once())->method('setUser')->with($userMock);
        $entityManager->expects($this->once())->method('flush');

        $doctrineRegistry = $this->createDoctrineRegistryMock($entityManager);

        /** @var ContainerInterface|MockObject $container */
        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->willReturnCallback(function ($serviceName) use ($entityManager, $doctrineRegistry) {
            if ($serviceName === 'doctrine.orm.entity_manager') {
                return $entityManager;
            }
            if ($serviceName === 'doctrine') {
                return $doctrineRegistry;
            }
            return null;
        });

        $command = new LinkAnonymousTasksCommand();
        $command->setContainer($container);

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $output = $commandTester->getDisplay();

        $this->assertSame(0, $commandTester->getStatusCode());
        $this->assertStringContainsString('Linked tasks to the generic anonymous user account successfully', $output);
    }

    /**
     * Test the CLI command when the virtual "anonyme" user does not exist yet.
     */
    public function testExecuteCommandCreatesVirtualUserWhenMissing()
    {
        $taskMock = $this->createMock(Task::class);

        /** @var EntityRepository|MockObject $userRepository */
        $userRepository = $this->createMock(EntityRepository::class);
        $userRepository->method('findOneBy')->with(['username' => 'anonyme'])->willReturn(null);

        /** @var EntityRepository|MockObject $taskRepository */
        $taskRepository = $this->createMock(EntityRepository::class);
        $taskRepository->method('findBy')->with(['user' => null])->willReturn([$taskMock]);

        /** @var UserPasswordEncoderInterface|MockObject $encoder */
        $encoder = $this->createMock(UserPasswordEncoderInterface::class);
        $encoder->method('encodePassword')->willReturn('hashed_password_mock');

        /** @var EntityManagerInterface|MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $entityManager->method('getRepository')->willReturnCallback(function ($entityName) use ($userRepository, $taskRepository) {
            if ($entityName === User::class || $entityName === 'AppBundle:User') {
                return $userRepository;
            }
            if ($entityName === Task::class || $entityName === 'AppBundle:Task') {
                return $taskRepository;
            }
            return null;
        });

        $entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(User::class));

        $entityManager->expects($this->atLeastOnce())
            ->method('flush');

        $doctrineRegistry = $this->createDoctrineRegistryMock($entityManager);

        /** @var ContainerInterface|MockObject $container */
        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->willReturnCallback(function ($serviceName) use ($entityManager, $encoder, $doctrineRegistry) {
            if ($serviceName === 'doctrine.orm.entity_manager') {
                return $entityManager;
            }
            if ($serviceName === 'doctrine') {
                return $doctrineRegistry;
            }
            if ($serviceName === 'security.password_encoder') {
                return $encoder;
            }
            return null;
        });

        $command = new LinkAnonymousTasksCommand();
        $command->setContainer($container);

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $output = $commandTester->getDisplay();

        $this->assertSame(0, $commandTester->getStatusCode());
        $this->assertStringContainsString('Virtual user "anonyme" created successfully.', $output);
    }
}
