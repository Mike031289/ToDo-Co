<?php

namespace Tests\AppBundle\Command;

use AppBundle\Command\LinkAnonymousTasksCommand;
use AppBundle\Entity\Task;
use AppBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class LinkAnonymousTasksCommandTest extends KernelTestCase
{
    /**
     * Test the CLI command behavior when data is already clean.
     */
    public function testExecuteCommandWhenSchemaIsClean()
    {
        self::bootKernel();

        $application = new Application(self::$kernel);
        $application->add(new LinkAnonymousTasksCommand());

        $command = $application->find('app:tasks:link-anonymous');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        $output = $commandTester->getDisplay();

        $this->assertSame(0, $commandTester->getStatusCode());
        $this->assertStringContainsString('Data clean-up complete', $output);
    }

    /**
     * Test the CLI command behavior when there are anonymous tasks to link.
     */
    public function testExecuteCommandWithAnonymousTasks()
    {
        self::bootKernel();
        $container = self::$kernel->getContainer();

        $userMock = $this->createMock(User::class);
        $taskMock = $this->createMock(Task::class);

        /** @var \PHPUnit\Framework\MockObject\MockObject|EntityRepository $userRepository */
        $userRepository = $this->createMock(EntityRepository::class);
        $userRepository->method('findOneBy')->with(['username' => 'anonyme'])->willReturn($userMock);

        /** @var \PHPUnit\Framework\MockObject\MockObject|EntityRepository $taskRepository */
        $taskRepository = $this->createMock(EntityRepository::class);
        $taskRepository->method('findBy')->with(['user' => null])->willReturn([$taskMock]);

        /** @var \PHPUnit\Framework\MockObject\MockObject|EntityManagerInterface $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getRepository')->willReturnMap([
            [User::class, $userRepository],
            [Task::class, $taskRepository],
        ]);

        $taskMock->expects($this->once())->method('setUser')->with($userMock);
        $entityManager->expects($this->once())->method('flush');

        $container->set('doctrine.orm.default_entity_manager', $entityManager);

        $application = new Application(self::$kernel);
        $application->add(new LinkAnonymousTasksCommand());

        $command = $application->find('app:tasks:link-anonymous');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        $output = $commandTester->getDisplay();

        $this->assertSame(0, $commandTester->getStatusCode());
        $this->assertStringContainsString('Linked tasks to the generic anonymous user account successfully', $output);
    }

    /**
     * Test the CLI command when the virtual "anonyme" user does not exist yet.
     * This hits the initialization block and spikes code coverage.
     */
    public function testExecuteCommandCreatesVirtualUserWhenMissing()
    {
        self::bootKernel();
        $container = self::$kernel->getContainer();

        // 1. Setup repo mock to return NULL (user doesn't exist)
        /** @var \PHPUnit\Framework\MockObject\MockObject|EntityRepository $userRepository */
        $userRepository = $this->createMock(EntityRepository::class);
        $userRepository->method('findOneBy')->with(['username' => 'anonyme'])->willReturn(null);

        /** @var \PHPUnit\Framework\MockObject\MockObject|EntityRepository $taskRepository */
        $taskRepository = $this->createMock(EntityRepository::class);
        $taskRepository->method('findBy')->with(['user' => null])->willReturn([]);

        // 2. Mock Encoder service required inside the creation block
        /** @var \PHPUnit\Framework\MockObject\MockObject|UserPasswordEncoderInterface $encoder */
        $encoder = $this->createMock(UserPasswordEncoderInterface::class);
        $encoder->method('encodePassword')->willReturn('hashed_password_mock');
        $container->set('security.password_encoder', $encoder);

        // 3. Setup Entity Manager Mock to intercept persists
        /** @var \PHPUnit\Framework\MockObject\MockObject|EntityManagerInterface $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getRepository')->willReturnMap([
            [User::class, $userRepository],
            [Task::class, $taskRepository],
        ]);

        // We expect the command to save the new user
        $entityManager->expects($this->once())->method('persist')->with($this->isInstanceOf(User::class));
        $entityManager->expects($this->once())->method('flush');

        $container->set('doctrine.orm.default_entity_manager', $entityManager);

        // 4. Run command
        $application = new Application(self::$kernel);
        $application->add(new LinkAnonymousTasksCommand());

        $command = $application->find('app:tasks:link-anonymous');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        $output = $commandTester->getDisplay();

        // 5. Assertions
        $this->assertSame(0, $commandTester->getStatusCode());
        $this->assertStringContainsString('Virtual user "anonyme" created successfully.', $output);
    }

    /**
     * Clean up the service container after each test execution.
     */
    protected function tearDown()
    {
        parent::tearDown();
        self::ensureKernelShutdown();
    }
}
