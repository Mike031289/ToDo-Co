<?php

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\User;
use AppBundle\Entity\Task;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadData implements FixtureInterface, ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $encoder = $this->container->get('security.password_encoder');

        // ==========================================
        // 1. CREATE USERS
        // ==========================================

        // Create Administrative User 'Mike'
        $adminUser = new User();
        $adminUser->setUsername('Mike');
        $adminUser->setEmail('mike@example.com');
        $adminUser->setRoles(['ROLE_ADMIN']);
        $adminUser->setPassword($encoder->encodePassword($adminUser, 'password123'));
        $manager->persist($adminUser);

        // Create a Standard User (Optional, but useful for testing)
        $regularUser = new User();
        $regularUser->setUsername('JohnDoe');
        $regularUser->setEmail('john@example.com');
        $regularUser->setRoles(['ROLE_USER']);
        $regularUser->setPassword($encoder->encodePassword($regularUser, 'password123'));
        $manager->persist($regularUser);

        // ==========================================
        // 2. CREATE TASKS LINKED TO ADMIN USER ('Mike')
        // ==========================================

        $adminTask1 = new Task();
        $adminTask1->setTitle('Admin Urgent Task');
        $adminTask1->setContent('Review code quality regressions and CI pipelines.');
        $adminTask1->setCreatedAt(new \DateTime());
        $adminTask1->toggle(false);
        $adminTask1->setUser($adminUser); // Linking to Mike
        $manager->persist($adminTask1);

        $adminTask2 = new Task();
        $adminTask2->setTitle('Completed Admin Task');
        $adminTask2->setContent('Setup DoctrineFixturesBundle structure in Symfony 3.1.');
        $adminTask2->setCreatedAt(new \DateTime('-1 day'));
        $adminTask2->toggle(true);
        $adminTask2->setUser($adminUser); // Linking to Mike
        $manager->persist($adminTask2);

        // ==========================================
        // 3. CREATE ANONYMOUS TASKS
        // ==========================================

        // Scenario A: Your Task entity allows NULL for the user relation (Standard Symfony layout)
        $anonymousTask1 = new Task();
        $anonymousTask1->setTitle('Anonymous Task One');
        $anonymousTask1->setContent('This task has no explicit author assigned.');
        $anonymousTask1->setCreatedAt(new \DateTime('-2 days'));
        $anonymousTask1->toggle(false);
        $anonymousTask1->setUser(null); // Explicitly anonymous (null)
        $manager->persist($anonymousTask1);

        $anonymousTask2 = new Task();
        $anonymousTask2->setTitle('Anonymous Task Two');
        $anonymousTask2->setContent('Another old legacy task safely kept for migration tests.');
        $anonymousTask2->setCreatedAt(new \DateTime('-3 days'));
        $anonymousTask2->toggle(false);
        $anonymousTask2->setUser(null); // Explicitly anonymous (null)
        $manager->persist($anonymousTask2);

        // ==========================================
        // 4. FLUSH EVERYTHING TO DATABASE
        // ==========================================
        $manager->flush();
    }
}
