<?php

namespace Tests\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use AppBundle\Entity\User;
use AppBundle\Entity\Task;

/**
 * Class TaskControllerTest
 *
 * Validates functional scenarios regarding task operations, focusing on security,
 * CRUD actions, and author-restricted deletion rules.
 *
 * @package Tests\AppBundle\Controller
 */
class TaskControllerTest extends WebTestCase
{
    /**
     * Helper method to create an authenticated client.
     *
     * @param string $username
     * @param string $password
     * @return \Symfony\Bundle\FrameworkBundle\Client
     */
    private function createAuthenticatedClient($username, $password)
    {
        return static::createClient([], [
            'PHP_AUTH_USER' => $username,
            'PHP_AUTH_PW'   => $password,
        ]);
    }

    /**
     * Test displaying the tasks list page.
     */
    public function testListTasks()
    {
        $client = $this->createAuthenticatedClient('Jean', 'password123');
        $crawler = $client->request('GET', '/tasks');

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertGreaterThan(0, $crawler->filter('html:contains("Créer une tâche")')->count());
    }

    /**
     * Test successful creation of a task.
     */
    public function testCreateTaskSuccess()
    {
        $client = $this->createAuthenticatedClient('Jean', 'password123');
        $crawler = $client->request('GET', '/tasks/create');

        $this->assertSame(200, $client->getResponse()->getStatusCode());

        $form = $crawler->selectButton('Ajouter')->form([
            'task[title]'   => 'New Task Title',
            'task[content]' => 'Content for the new task.',
        ]);

        $client->submit($form);

        $this->assertTrue($client->getResponse()->isRedirect());
        $crawler = $client->followRedirect();

        $this->assertGreaterThan(0, $crawler->filter('.alert-success')->count());
        $this->assertContains('La tâche a bien été ajoutée.', $client->getResponse()->getContent());
    }

    /**
     * Test task creation failure when submitted data is invalid/blank.
     */
    public function testCreateTaskFailure()
    {
        $client = $this->createAuthenticatedClient('Jean', 'password123');
        $crawler = $client->request('GET', '/tasks/create');

        $form = $crawler->selectButton('Ajouter')->form([
            'task[title]'   => '', // Vide pour déclencher l'erreur de validation Assert\NotBlank
            'task[content]' => 'Some content.',
        ]);

        // Force la désactivation de la validation native HTML5 au cas où elle bloquerait la soumission du formulaire vide
        $form->getFormNode()->removeAttribute('novalidate');

        $crawler = $client->submit($form);

        // Devrait rester sur la page (200 OK) avec les messages d'erreurs
        $this->assertSame(200, $client->getResponse()->getStatusCode());

        // Si ".has-error" échoue encore, tente de chercher les textes de contraintes ("Vous devez saisir un titre.")
        $this->assertContains('Vous devez saisir un titre.', $client->getResponse()->getContent());
    }

    /**
     * Test modifying an existing task.
     */
    public function testEditTask()
    {
        $client = $this->createAuthenticatedClient('Jean', 'password123');
        $em = $client->getContainer()->get('doctrine')->getManager();

        /** @var User $jean */
        $jean = $em->getRepository(User::class)->findOneBy(['username' => 'Jean']);

        $task = new Task();
        $task->setTitle('Task to Edit');
        $task->setContent('Original Content');
        $task->setUser($jean);
        $em->persist($task);
        $em->flush();

        $crawler = $client->request('GET', sprintf('/tasks/%d/edit', $task->getId()));
        $this->assertSame(200, $client->getResponse()->getStatusCode());

        $form = $crawler->selectButton('Modifier')->form([
            'task[title]'   => 'Updated Task Title',
            'task[content]' => 'Updated Content.',
        ]);

        $client->submit($form);

        $this->assertTrue($client->getResponse()->isRedirect());
        $crawler = $client->followRedirect();

        $this->assertGreaterThan(0, $crawler->filter('.alert-success')->count());
        $this->assertContains('La tâche a bien été modifiée.', $client->getResponse()->getContent());
    }

    /**
     * Test toggling a task status (Done / Todo switch).
     */
    public function testToggleTaskStatus()
    {
        $client = $this->createAuthenticatedClient('Jean', 'password123');
        $em = $client->getContainer()->get('doctrine')->getManager();

        /** @var User $jean */
        $jean = $em->getRepository(User::class)->findOneBy(['username' => 'Jean']);

        $task = new Task();
        $task->setTitle('Toggle Status Task');
        $task->setContent('Content.');
        $task->setUser($jean);
        $task->setIsDone(false);
        $em->persist($task);
        $em->flush();

        $client->request('GET', sprintf('/tasks/%d/toggle', $task->getId()));

        $this->assertTrue($client->getResponse()->isRedirect());
        $crawler = $client->followRedirect();

        $this->assertGreaterThan(0, $crawler->filter('.alert-success')->count());

        // FIX: On récupère à nouveau l'entité depuis le Repository pour éviter le bug de détachement Doctrine
        $updatedTask = $em->getRepository(Task::class)->find($task->getId());

        $this->assertNotNull($updatedTask);
        $this->assertTrue($updatedTask->isDone());
    }

    /**
     * Test that a user cannot delete another user's task.
     */
    public function testUserCannotDeleteOtherUsersTask()
    {
        $client = $this->createAuthenticatedClient('Jean', 'password123');
        $container = $client->getContainer();
        $em = $container->get('doctrine')->getManager();

        /** @var User $otherUser */
        $otherUser = $em->getRepository(User::class)->findOneBy(['username' => 'Mike']);
        $this->assertNotNull($otherUser, "User 'Mike' must exist in the test database.");

        $task = new Task();
        $task->setTitle('Mikes Task');
        $task->setContent('Confidential content');
        $task->setUser($otherUser);

        $em->persist($task);
        $em->flush();

        $client->request('GET', sprintf('/tasks/%d/delete', $task->getId()));

        $this->assertEquals(403, $client->getResponse()->getStatusCode());
    }

    /**
     * Test that a user can successfully delete their own task.
     */
    public function testUserCanDeleteOwnTask()
    {
        $client = $this->createAuthenticatedClient('Jean', 'password123');
        $container = $client->getContainer();
        $em = $container->get('doctrine')->getManager();

        /** @var User $jean */
        $jean = $em->getRepository(User::class)->findOneBy(['username' => 'Jean']);
        $this->assertNotNull($jean, "User 'Jean' must exist in the test database.");

        $task = new Task();
        $task->setTitle('My super task');
        $task->setContent('I must complete this task, and I have the permission to delete it.');
        $task->setUser($jean);

        $em->persist($task);
        $em->flush();

        $client->request('GET', sprintf('/tasks/%d/delete', $task->getId()));

        $this->assertEquals(302, $client->getResponse()->getStatusCode());

        $client->followRedirect();
        $this->assertContains('La tâche a bien été supprimée.', $client->getResponse()->getContent());
    }
}
