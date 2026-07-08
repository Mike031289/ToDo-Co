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
        // 1. CREATE USERS (Including Virtual "anonyme")
        // ==========================================

        // Create Administrative User 'Mike'
        $adminUser = new User();
        $adminUser->setUsername('Mike');
        $adminUser->setEmail('mike@example.com');
        $adminUser->setRoles(['ROLE_ADMIN']);
        $adminUser->setPassword($encoder->encodePassword($adminUser, 'password123'));
        $manager->persist($adminUser);

        // Create a Standard User
        $regularUser = new User();
        $regularUser->setUsername('JohnDoe');
        $regularUser->setEmail('john@example.com');
        $regularUser->setRoles(['ROLE_USER']);
        $regularUser->setPassword($encoder->encodePassword($regularUser, 'password123'));
        $manager->persist($regularUser);

        // Create the Virtual "anonyme" User for Legacy Data Integrity
        $anonymousUser = new User();
        $anonymousUser->setUsername('anonyme');
        $anonymousUser->setEmail('anonymous@todo-co.local');
        $anonymousUser->setRoles(['ROLE_USER']);
        // Generates a random unguessable password since nobody logs into this specific profile
        $anonymousUser->setPassword($encoder->encodePassword($anonymousUser, bin2hex(random_bytes(16))));
        $manager->persist($anonymousUser);

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
        // 3. CREATE LEGACY ANONYMOUS TASKS
        // ==========================================

        // Linked to the virtual "anonyme" object to prevent schema/nullable conflicts
        $anonymousTask1 = new Task();
        $anonymousTask1->setTitle('Anonymous Task One');
        $anonymousTask1->setContent('This task has no explicit author assigned.');
        $anonymousTask1->setCreatedAt(new \DateTime('-2 days'));
        $anonymousTask1->toggle(false);
        $anonymousTask1->setUser($anonymousUser); // Clean migration link
        $manager->persist($anonymousTask1);

        $anonymousTask2 = new Task();
        $anonymousTask2->setTitle('Anonymous Task Two');
        $anonymousTask2->setContent('Another old legacy task safely kept for migration tests.');
        $anonymousTask2->setCreatedAt(new \DateTime('-3 days'));
        $anonymousTask2->toggle(false);
        $anonymousTask2->setUser($anonymousUser); // Clean migration link
        $manager->persist($anonymousTask2);

        // ==========================================
        // 4. FLUSH EVERYTHING TO DATABASE
        // ==========================================
        $manager->flush();
    }
}
