<?php

namespace AppBundle\Command;

use AppBundle\Entity\Task;
use AppBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class LinkAnonymousTasksCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('app:tasks:link-anonymous')
            ->setDescription('Binds all unauthored legacy tasks to a default virtual "anonyme" user account.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $em = $this->getContainer()->get('doctrine')->getManager();

        // 1. Check or create the virtual "anonyme" user
        $userRepository = $em->getRepository(User::class);
        $anonymousUser = $userRepository->findOneBy(['username' => 'anonyme']);

        if (!$anonymousUser) {
            $io->note('The virtual user "anonyme" does not exist. Creating it now...');

            $anonymousUser = new User();
            $anonymousUser->setUsername('anonyme');
            $anonymousUser->setEmail('anonymous@todo-co.local');
            $anonymousUser->setRoles(['ROLE_USER']);

            // Secure random password generation since nobody should log in directly with this account
            $encoder = $this->getContainer()->get('security.password_encoder');
            $randomPassword = bin2hex(random_bytes(16));
            $hashedPassword = $encoder->encodePassword($anonymousUser, $randomPassword);
            $anonymousUser->setPassword($hashedPassword);

            $em->persist($anonymousUser);
            $em->flush();

            $io->success('Virtual user "anonyme" created successfully.');
        }

        // 2. Fetch and migration tasks with NULL user relations
        $taskRepository = $em->getRepository(Task::class);
        $orphanTasks = $taskRepository->findBy(['user' => null]);

        $taskCount = count($orphanTasks);
        if ($taskCount === 0) {
            // Correspond exactement à testExecuteCommandWhenSchemaIsClean
            $io->success('Data clean-up complete: No orphan tasks found with a NULL author.');
            return 0;
        }

        $io->progressStart($taskCount);
        foreach ($orphanTasks as $task) {
            $task->setUser($anonymousUser);
            $io->progressAdvance();
        }

        $em->flush();
        $io->progressFinish();

        // Correspond exactement à testExecuteCommandWithAnonymousTasks
        $io->success('Linked tasks to the generic anonymous user account successfully');

        return 0;
    }
}
