<?php

namespace Tests\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use AppBundle\Entity\User;
use AppBundle\Entity\Task;

/**
 * Class TaskControllerTest
 *
 * @package Tests\AppBundle\Controller
 * @covers \AppBundle\Controller\TaskController
 */
class TaskControllerTest extends WebTestCase
{
    /**
     * Helper method to create an HTTP client authenticated via HTTP Basic Auth.
     *
     * @param string $username
     * @param string $password
     * @return \Symfony\Bundle\FrameworkBundle\Client
     */
    private function createAuthenticatedClient($username, $password)
    {
        // Force kernel shutdown to clear any polluted container state from previous test suites
        self::ensureKernelShutdown();

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

        $this->assertSame(200, $client->getResponse()->getStatusCode(), "FAILED: The route /tasks does not return a 200 OK.");
        $this->assertGreaterThan(0, $crawler->filter('html:contains("Créer une tâche")')->count());
    }

    /**
     * Test successful creation of a task.
     */
    public function testCreateTaskSuccess()
    {
        $client = $this->createAuthenticatedClient('Jean', 'password123');
        $crawler = $client->request('GET', '/tasks/create');

        $this->assertSame(200, $client->getResponse()->getStatusCode(), "FAILED: Route /tasks/create not found or inaccessible.");

        // Target the form structure to fill input values
        $form = $crawler->filter('form')->form([
            'task[title]'   => 'New Task Title',
            'task[content]' => 'Content for the new task.',
        ]);

        $client->submit($form);

        // If validation fails, dump the HTML response content to inspect form errors
        if ($client->getResponse()->isRedirect() === false) {
            fwrite(STDERR, "\n[FORM ERROR IN testCreateTaskSuccess]:\n" . $client->getResponse()->getContent() . "\n");
        }

        $this->assertTrue($client->getResponse()->isRedirect(), "FAILED: createAction did not redirect after successful form submission.");
        $client->followRedirect();

        $this->assertContains('La tâche a bien été ajoutée.', $client->getResponse()->getContent());
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

        $this->assertSame(200, $client->getResponse()->getStatusCode(), sprintf("FAILED: Edit route for ID %d returned status %d instead of 200.", $task->getId(), $client->getResponse()->getStatusCode()));

        $form = $crawler->filter('form')->form([
            'task[title]'   => 'Updated Task Title',
            'task[content]' => 'Updated Content.',
        ]);

        $client->submit($form);

        // If submission fails, dump the HTML payload to identify constraints violations
        if (!$client->getResponse()->isRedirect()) {
            fwrite(STDERR, "\n[FORM ERROR IN testEditTask]:\n" . $client->getResponse()->getContent() . "\n");
        }

        $this->assertTrue($client->getResponse()->isRedirect(), "FAILED: editAction did not redirect after success.");
        $client->followRedirect();

        $this->assertContains('La tâche a bien été modifiée.', $client->getResponse()->getContent());
    }

    /**
     * Test toggling a task status.
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

        // Check for 302 redirect code explicitly to guarantee coverage transition
        $this->assertSame(302, $client->getResponse()->getStatusCode(), sprintf("FAILED: Toggle route returned status %d instead of a 302 redirect.", $client->getResponse()->getStatusCode()));

        $client->followRedirect();

        $em->clear();
        $updatedTask = $em->getRepository(Task::class)->find($task->getId());

        $this->assertNotNull($updatedTask);
        $this->assertTrue($updatedTask->isDone());
    }

    /**
     * Test deleting a task successfully.
     */
    public function testDeleteTaskSuccess()
    {
        $client = $this->createAuthenticatedClient('Jean', 'password123');
        $em = $client->getContainer()->get('doctrine')->getManager();

        /** @var User $jean */
        $jean = $em->getRepository(User::class)->findOneBy(['username' => 'Jean']);

        // Create a task bound to the authenticated user to pass Voter authorization policies
        $task = new Task();
        $task->setTitle('Task to Delete');
        $task->setContent('Content.');
        $task->setUser($jean);
        $em->persist($task);
        $em->flush();

        // Store the ID before running the deletion request
        $taskId = $task->getId();

        $client->request('GET', sprintf('/tasks/%d/delete', $taskId));

        $this->assertSame(302, $client->getResponse()->getStatusCode(), "FAILED: Delete route did not redirect.");

        $client->followRedirect();

        $this->assertContains('La tâche a bien été supprimée.', $client->getResponse()->getContent());

        // Inspect the database lifecycle state using the stored ID to ensure entity deletion occurred
        $em->clear();
        $deletedTask = $em->getRepository(Task::class)->find($taskId);
        $this->assertNull($deletedTask, "FAILED: The task was not removed from the database.");
    }
}
