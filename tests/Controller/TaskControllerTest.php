<?php

namespace App\Tests\Controller;

use App\Entity\Task;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class TaskControllerTest
 *
 * Comprehensive functional test suite for task management endpoints in Symfony 5.4.
 *
 * @package App\Tests\Controller
 * @covers \App\Controller\TaskController
 */
class TaskControllerTest extends WebTestCase
{
    /**
     * Helper to create an authenticated client using Symfony 5.1+ loginUser feature.
     *
     * @param string $username
     * @param array $roles
     * @return KernelBrowser
     */
    private function createAuthenticatedUserClient(string $username = 'Jean', array $roles = ['ROLE_USER']): KernelBrowser
    {
        // Règle d'or : Appeler createClient() AVANT de récupérer le container
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();

        /** @var User|null $user */
        $user = $em->getRepository(User::class)->findOneBy(['username' => $username]);

        if (null === $user) {
            $user = new User();
            $user->setUsername($username);
            $user->setEmail(strtolower($username) . '_task_test@example.com');
            $user->setRoles($roles);

            $hasher = $container->get('security.user_password_hasher');
            $hashedPassword = $hasher->hashPassword($user, 'password123');
            $user->setPassword($hashedPassword);

            $em->persist($user);
            $em->flush();
        }

        $client->loginUser($user);

        return $client;
    }

    /**
     * Test displaying the active tasks list page.
     */
    public function testListTasks(): void
    {
        $client = $this->createAuthenticatedUserClient('Jean');
        $crawler = $client->request('GET', '/tasks');

        $this->assertResponseIsSuccessful("FAILED: The route /tasks did not return a 200 OK status.");
        $this->assertGreaterThan(0, $crawler->filter('html:contains("Créer une tâche")')->count());
    }

    /**
     * Test successful creation of a task.
     */
    public function testCreateTaskSuccess(): void
    {
        $client = $this->createAuthenticatedUserClient('Jean');
        $crawler = $client->request('GET', '/tasks/create');

        $this->assertResponseIsSuccessful("FAILED: Route /tasks/create not found or inaccessible.");

        $form = $crawler->filter('form')->form([
            'task[title]'   => 'New Task Title ' . uniqid(),
            'task[content]' => 'Content for the new task.',
        ]);

        $client->submit($form);

        $this->assertResponseRedirects(null, null, "FAILED: Task creation did not trigger a redirect response status.");
        $crawler = $client->followRedirect();

        $this->assertGreaterThan(
            0,
            $crawler->filter('.alert-success:contains("La tâche a bien été ajoutée.")')->count(),
            "FAILED: Flash message for task creation missing or misspelled."
        );
    }

    /**
     * Test task creation form validation failure.
     */
    public function testCreateTaskValidationFailure(): void
    {
        $client = $this->createAuthenticatedUserClient('Jean');
        $crawler = $client->request('GET', '/tasks/create');

        $form = $crawler->filter('form')->form([
            'task[title]'   => '',
            'task[content]' => '',
        ]);

        $client->submit($form);

        $this->assertResponseIsSuccessful();
        $this->assertFalse($client->getResponse()->isRedirect());
    }

    /**
     * Test modifying an existing task.
     */
    public function testEditTask(): void
    {
        $client = $this->createAuthenticatedUserClient('Jean');
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();

        /** @var User $jean */
        $jean = $em->getRepository(User::class)->findOneBy(['username' => 'Jean']);

        $task = new Task();
        $task->setTitle('Task to Edit ' . uniqid());
        $task->setContent('Original Content');
        $task->setUser($jean);

        $em->persist($task);
        $em->flush();

        $crawler = $client->request('GET', sprintf('/tasks/%d/edit', $task->getId()));
        $this->assertResponseIsSuccessful();

        $form = $crawler->filter('form')->form([
            'task[title]'   => 'Updated Task Title',
            'task[content]' => 'Updated Content.',
        ]);

        $client->submit($form);

        $this->assertResponseRedirects();
        $crawler = $client->followRedirect();

        $this->assertGreaterThan(
            0,
            $crawler->filter('.alert-success:contains("La tâche a bien été modifiée.")')->count()
        );
    }
}
