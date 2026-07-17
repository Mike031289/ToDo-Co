<?php

namespace Tests\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use AppBundle\Entity\User;
use AppBundle\Entity\Task;

/**
 * Class TaskControllerTest
 *
 * Validates functional scenarios regarding task operations, focusing on security
 * and author-restricted deletion rules.
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
     * Test that a user cannot delete another user's task.
     *
     * @return void
     */
    public function testUserCannotDeleteOtherUsersTask()
    {
        $client = $this->createAuthenticatedClient('Jean', 'password123');
        $container = $client->getContainer();
        $em = $container->get('doctrine')->getManager();

        // 1. Retrieve another user (e.g., admin user 'Mike')
        /** @var User $otherUser */
        $otherUser = $em->getRepository(User::class)->findOneBy(['username' => 'Mike']);
        $this->assertNotNull($otherUser, "User 'Mike' must exist in the test database.");

        // 2. Create a dedicated task for this user to test restriction
        $task = new Task();
        $task->setTitle('Mikes Task');
        $task->setContent('Confidential content');
        $task->setUser($otherUser);

        $em->persist($task);
        $em->flush();

        // 3. Jean attempts to delete Mike's task
        $client->request('GET', sprintf('/tasks/%d/delete', $task->getId()));

        // 4. Verify that Jean is blocked with a 403 Forbidden status code
        $this->assertEquals(403, $client->getResponse()->getStatusCode());

        // Clean up the test database
        $em->refresh($task); // Ensure the entity state is refreshed
    }

    /**
     * Test that a user can successfully delete their own task.
     *
     * @return void
     */
    public function testUserCanDeleteOwnTask()
    {
        $client = $this->createAuthenticatedClient('Jean', 'password123');
        $container = $client->getContainer();
        $em = $container->get('doctrine')->getManager();

        // 1. Retrieve the user 'Jean'
        /** @var User $jean */
        $jean = $em->getRepository(User::class)->findOneBy(['username' => 'Jean']);
        $this->assertNotNull($jean, "User 'Jean' must exist in the test database.");

        // 2. Create a task owned by Jean
        $task = new Task();
        $task->setTitle('My super task');
        $task->setContent('I must complete this task, and I have the permission to delete it.');
        $task->setUser($jean);

        $em->persist($task);
        $em->flush();

        // 3. Jean attempts to delete his own task
        $client->request('GET', sprintf('/tasks/%d/delete', $task->getId()));

        // 4. Verify that he is redirected (302) to the list page
        $this->assertEquals(302, $client->getResponse()->getStatusCode());

        $client->followRedirect();
        $this->assertContains('La tâche a bien été supprimée.', $client->getResponse()->getContent());
    }
}
