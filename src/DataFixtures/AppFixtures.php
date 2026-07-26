<?php

namespace App\DataFixtures;

use App\Entity\Task;
use App\Entity\User;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Class AppFixtures
 *
 * Seeds the database with default administrative, standard, and anonymous users,
 * alongside associated tasks to enable robust functional security and permission testing.
 */
class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $userPasswordHasher;

    public function __construct(UserPasswordHasherInterface $userPasswordHasher)
    {
        $this->userPasswordHasher = $userPasswordHasher;
    }

    /**
     * Load data fixtures into the database.
     */
    public function load(ObjectManager $manager): void
    {
        $users = $this->loadUsers($manager);

        $this->loadAdminTasks($manager, $users['admin']);
        $this->loadUserTasks($manager, $users['jean'], $users['sophie']);
        $this->loadAnonymousTasks($manager, $users['anonymous']);

        $manager->flush();
    }

    /**
     * Creates and persists default application users.
     *
     * @return array<string, User>
     */
    private function loadUsers(ObjectManager $manager): array
    {
        $adminUser = new User();
        $adminUser->setUsername('Mike');
        $adminUser->setEmail('mike@example.com');
        $adminUser->setRoles(['ROLE_ADMIN']);
        $adminUser->setPassword(
            $this->userPasswordHasher->hashPassword($adminUser, 'password123')
        );
        $manager->persist($adminUser);

        $regularUserJean = new User();
        $regularUserJean->setUsername('Jean');
        $regularUserJean->setEmail('jean@example.com');
        $regularUserJean->setRoles(['ROLE_USER']);
        $regularUserJean->setPassword(
            $this->userPasswordHasher->hashPassword($regularUserJean, 'password123')
        );
        $manager->persist($regularUserJean);

        $regularUserSophie = new User();
        $regularUserSophie->setUsername('Sophie');
        $regularUserSophie->setEmail('sophie@example.com');
        $regularUserSophie->setRoles(['ROLE_USER']);
        $regularUserSophie->setPassword(
            $this->userPasswordHasher->hashPassword($regularUserSophie, 'password123')
        );
        $manager->persist($regularUserSophie);

        $regularUserJohn = new User();
        $regularUserJohn->setUsername('JohnDoe');
        $regularUserJohn->setEmail('john@example.com');
        $regularUserJohn->setRoles(['ROLE_USER']);
        $regularUserJohn->setPassword(
            $this->userPasswordHasher->hashPassword($regularUserJohn, 'password123')
        );
        $manager->persist($regularUserJohn);

        $anonymousUser = new User();
        $anonymousUser->setUsername('anonyme');
        $anonymousUser->setEmail('anonymous@todo-co.local');
        $anonymousUser->setRoles(['ROLE_USER']);
        $anonymousUser->setPassword(
            $this->userPasswordHasher->hashPassword($anonymousUser, bin2hex(random_bytes(16)))
        );
        $manager->persist($anonymousUser);

        return [
            'admin'     => $adminUser,
            'jean'      => $regularUserJean,
            'sophie'    => $regularUserSophie,
            'anonymous' => $anonymousUser,
        ];
    }

    /**
     * Creates tasks assigned to the administrative account.
     */
    private function loadAdminTasks(ObjectManager $manager, User $adminUser): void
    {
        $adminTask1 = new Task();
        $adminTask1->setTitle('Tâche urgente Admin');
        $adminTask1->setContent('Passer en revue les régressions de qualité du code et les pipelines CI.');
        $adminTask1->setCreatedAt(new DateTime());
        $adminTask1->toggle(false);
        $adminTask1->setUser($adminUser);
        $manager->persist($adminTask1);

        $adminTask2 = new Task();
        $adminTask2->setTitle('Tâche Admin terminée');
        $adminTask2->setContent('Mettre en place la structure du DoctrineFixturesBundle dans Symfony 5.4.');
        $adminTask2->setCreatedAt(new DateTime('-1 day'));
        $adminTask2->toggle(true);
        $adminTask2->setUser($adminUser);
        $manager->persist($adminTask2);
    }

    /**
     * Creates tasks assigned to standard user accounts.
     */
    private function loadUserTasks(ObjectManager $manager, User $jean, User $sophie): void
    {
        $jeanTask1 = new Task();
        $jeanTask1->setTitle('Tâche personnelle de Jean');
        $jeanTask1->setContent('Écrire les tests fonctionnels pour le système de restriction de suppression des tâches.');
        $jeanTask1->setCreatedAt(new DateTime());
        $jeanTask1->toggle(false);
        $jeanTask1->setUser($jean);
        $manager->persist($jeanTask1);

        $sophieTask1 = new Task();
        $sophieTask1->setTitle('Tâche personnelle de Sophie');
        $sophieTask1->setContent('Rédiger les spécifications de l’expérience utilisateur pour le menu de navigation.');
        $sophieTask1->setCreatedAt(new DateTime());
        $sophieTask1->toggle(false);
        $sophieTask1->setUser($sophie);
        $manager->persist($sophieTask1);
    }

    /**
     * Creates legacy tasks associated with the virtual anonymous user profile.
     */
    private function loadAnonymousTasks(ObjectManager $manager, User $anonymousUser): void
    {
        $anonymousTask1 = new Task();
        $anonymousTask1->setTitle('Tâche anonyme un');
        $anonymousTask1->setContent('Cette tâche n’a pas d’auteur explicitement assigné.');
        $anonymousTask1->setCreatedAt(new DateTime('-2 days'));
        $anonymousTask1->toggle(false);
        $anonymousTask1->setUser($anonymousUser);
        $manager->persist($anonymousTask1);

        $anonymousTask2 = new Task();
        $anonymousTask2->setTitle('Tâche anonyme deux');
        $anonymousTask2->setContent('Une autre ancienne tâche historique conservée pour les tests de migration.');
        $anonymousTask2->setCreatedAt(new DateTime('-3 days'));
        $anonymousTask2->toggle(false);
        $anonymousTask2->setUser($anonymousUser);
        $manager->persist($anonymousTask2);
    }
}
