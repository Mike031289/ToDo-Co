<?php

namespace App\Command;

use App\Entity\Task;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Class LinkAnonymousTasksCommand
 *
 * Console command that attaches legacy tasks with no assigned author (user = NULL)
 * to a dedicated virtual "anonyme" user account.
 *
 * @package App\Command
 */
class LinkAnonymousTasksCommand extends Command
{
    /**
     * @var string|null Default name for CLI invocation
     */
    protected static $defaultName = 'app:tasks:link-anonymous';

    /**
     * @var string|null Default description for CLI helper
     */
    protected static $defaultDescription = 'Binds all unauthored legacy tasks to a default virtual "anonyme" user account.';

    private readonly EntityManagerInterface $entityManager;
    private readonly UserPasswordHasherInterface $passwordHasher;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
    }

    protected function configure(): void
    {
        $this->setDescription(self::$defaultDescription);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // 1. Check or create the virtual "anonyme" user
        $userRepository = $this->entityManager->getRepository(User::class);
        $anonymousUser = $userRepository->findOneBy(['username' => 'anonyme']);

        if (null === $anonymousUser) {
            $io->note('The virtual user "anonyme" does not exist. Creating it now...');

            $anonymousUser = new User();
            $anonymousUser->setUsername('anonyme');
            $anonymousUser->setEmail('anonymous@todo-co.local');
            $anonymousUser->setRoles(['ROLE_USER']);

            // Secure random password generation using modern UserPasswordHasherInterface
            $randomPassword = bin2hex(random_bytes(16));
            $hashedPassword = $this->passwordHasher->hashPassword($anonymousUser, $randomPassword);
            $anonymousUser->setPassword($hashedPassword);

            $this->entityManager->persist($anonymousUser);
            $this->entityManager->flush();

            $io->success('Virtual user "anonyme" created successfully.');
        }

        // 2. Fetch and migrate tasks with NULL user relations
        $taskRepository = $this->entityManager->getRepository(Task::class);
        $orphanTasks = $taskRepository->findBy(['user' => null]);

        $taskCount = count($orphanTasks);
        if (0 === $taskCount) {
            $io->success('Data clean-up complete: No orphan tasks found with a NULL author.');
            return Command::SUCCESS;
        }

        $io->progressStart($taskCount);
        foreach ($orphanTasks as $task) {
            $task->setUser($anonymousUser);
            $io->progressAdvance();
        }

        $this->entityManager->flush();
        $io->progressFinish();

        $io->success('Linked tasks to the generic anonymous user account successfully.');

        return Command::SUCCESS;
    }
}
