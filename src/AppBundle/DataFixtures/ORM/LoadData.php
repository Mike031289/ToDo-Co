<?php

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\User;
use AppBundle\Entity\Task;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class LoadData
 *
 * Seeds the database with default administrative, standard, and anonymous users,
 * alongside associated tasks to enable robust functional security and permission testing.
 *
 * @package AppBundle\DataFixtures\ORM
 */
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

        // Create a Standard User 'Jean' (For standard workflow tests)
        $regularUserJean = new User();
        $regularUserJean->setUsername('Jean');
        $regularUserJean->setEmail('jean@example.com');
        $regularUserJean->setRoles(['ROLE_USER']);
        $regularUserJean->setPassword($encoder->encodePassword($regularUserJean, 'password123'));
        $manager->persist($regularUserJean);

        // Create a Standard User 'Sophie' (For cross-user deletion restriction tests)
        $regularUserSophie = new User();
        $regularUserSophie->setUsername('Sophie');
        $regularUserSophie->setEmail('sophie@example.com');
        $regularUserSophie->setRoles(['ROLE_USER']);
        $regularUserSophie->setPassword($encoder->encodePassword($regularUserSophie, 'password123'));
        $manager->persist($regularUserSophie);

        // Create a Standard User 'JohnDoe' (For multi-user separation tests)
        $regularUserJohn = new User();
        $regularUserJohn->setUsername('JohnDoe');
        $regularUserJohn->setEmail('john@example.com');
        $regularUserJohn->setRoles(['ROLE_USER']);
        $regularUserJohn->setPassword($encoder->encodePassword($regularUserJohn, 'password123'));
        $manager->persist($regularUserJohn);

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
        $adminTask1->setTitle('Tâche urgente Admin');
        $adminTask1->setContent('Passer en revue les régressions de qualité du code et les pipelines CI.');
        $adminTask1->setCreatedAt(new \DateTime());
        $adminTask1->toggle(false);
        $adminTask1->setUser($adminUser); // Linking to Mike
        $manager->persist($adminTask1);

        $adminTask2 = new Task();
        $adminTask2->setTitle('Tâche Admin terminée');
        $adminTask2->setContent('Mettre en place la structure du DoctrineFixturesBundle dans Symfony 3.4.');
        $adminTask2->setCreatedAt(new \DateTime('-1 day'));
        $adminTask2->toggle(true);
        $adminTask2->setUser($adminUser); // Linking to Mike
        $manager->persist($adminTask2);

        // ==========================================
        // 3. CREATE TASKS LINKED TO STANDARD USER ('Jean')
        // ==========================================

        $jeanTask1 = new Task();
        $jeanTask1->setTitle('Tâche personnelle de Jean');
        $jeanTask1->setContent('Écrire les tests fonctionnels pour le système de restriction de suppression des tâches.');
        $jeanTask1->setCreatedAt(new \DateTime());
        $jeanTask1->toggle(false);
        $jeanTask1->setUser($regularUserJean); // Linking to Jean
        $manager->persist($jeanTask1);

        // ==========================================
        // 4. CREATE TASKS LINKED TO STANDARD USER ('Sophie')
        // ==========================================

        $sophieTask1 = new Task();
        $sophieTask1->setTitle('Tâche personnelle de Sophie');
        $sophieTask1->setContent('Rédiger les spécifications de l’expérience utilisateur pour le menu de navigation.');
        $sophieTask1->setCreatedAt(new \DateTime());
        $sophieTask1->toggle(false);
        $sophieTask1->setUser($regularUserSophie); // Linking to Sophie
        $manager->persist($sophieTask1);

        // ==========================================
        // 5. CREATE LEGACY ANONYMOUS TASKS
        // ==========================================

        // Linked to the virtual "anonyme" object to prevent schema/nullable conflicts
        $anonymousTask1 = new Task();
        $anonymousTask1->setTitle('Tâche anonyme un');
        $anonymousTask1->setContent('Cette tâche n’a pas d’auteur explicitement assigné.');
        $anonymousTask1->setCreatedAt(new \DateTime('-2 days'));
        $anonymousTask1->toggle(false);
        $anonymousTask1->setUser($anonymousUser); // Clean migration link
        $manager->persist($anonymousTask1);

        $anonymousTask2 = new Task();
        $anonymousTask2->setTitle('Tâche anonyme deux');
        $anonymousTask2->setContent('Une autre ancienne tâche historique conservée pour les tests de migration.');
        $anonymousTask2->setCreatedAt(new \DateTime('-3 days'));
        $anonymousTask2->toggle(false);
        $anonymousTask2->setUser($anonymousUser); // Clean migration link
        $manager->persist($anonymousTask2);

        // ==========================================
        // 6. FLUSH EVERYTHING TO DATABASE
        // ==========================================
        $manager->flush();
    }
}
