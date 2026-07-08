<?php

namespace Tests\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TaskControllerTest extends WebTestCase
{
    /**
     * Test creating a task successfully
     */
    public function testCreateTaskSuccess()
    {
        // 1. Initialize the client with HTTP Basic Auth credentials to bypass session management isolation
        $client = static::createClient([], [
            'PHP_AUTH_USER' => 'Mike',
            'PHP_AUTH_PW'   => 'password123',
        ]);
        $container = $client->getContainer();

        // 2. Request the task creation page dynamically using the router service
        $crawler = $client->request('GET', $container->get('router')->generate('task_create'));

        // 3. Assert that the creation page is successfully accessible (HTTP 200)
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        // 4. Select the submission button and populate the form data structures
        $form = $crawler->selectButton('Ajouter la tâche')->form();
        $form['task[title]'] = 'New Task From PHPUnit';
        $form['task[content]'] = 'Testing automated task submission.';

        // 5. Submit the populated form
        $client->submit($form);

        // 6. Assert that the application triggers a redirect response (HTTP 302)
        $this->assertEquals(302, $client->getResponse()->getStatusCode());

        // 7. Follow the redirect to the target page and capture the new HTML content
        $crawler = $client->followRedirect();

        // 8. Assert that a success flash notification is displayed on screen
        $this->assertGreaterThan(
            0,
            $crawler->filter('.alert-success')->count(),
            'Expected a success flash message to be displayed.'
        );
    }

    /**
     * Test editing a task successfully
     * Test that editing a task does not alter or clear its original user
     */
    public function testEditTaskUserRemainsImmutable()
    {
        // 1. Authenticate the client using our robust HTTP Basic Auth setup
        $client = static::createClient([], [
            'PHP_AUTH_USER' => 'Mike',
            'PHP_AUTH_PW'   => 'password123', // Replace with your exact fixture password
        ]);
        $container = $client->getContainer();
        $em = $container->get('doctrine')->getManager();

        // 2. Retrieve a task from the database that already has an author
        $task = $em->getRepository('AppBundle:Task')->findOneBy([]);
        if (($task !== null) === false) {
            $this->fail('No task found in the database to run the edit test.');
        }

        $originalUser = $task->getUser(); // Save the original entity reference to compare later
        $taskId = $task->getId();

        // 3. Request the edit page for this specific task
        $crawler = $client->request('GET', '/tasks/' . $taskId . '/edit');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        // 4. Submit the form with new details
        $form = $crawler->selectButton('Modifier')->form(); // Adjust button text if it's different (e.g. 'Sauvegarder')
        $form['task[title]'] = 'Strictly Updated Title';
        $form['task[content]'] = 'Verifying author data integrity during POST submission.';

        $client->submit($form);
        $this->assertEquals(302, $client->getResponse()->getStatusCode());

        // 5. Clear the EntityManager to force reload fresh data from the database
        $em->clear();

        // 6. Fetch the updated task and assert data integrity
        $updatedTask = $em->getRepository('AppBundle:Task')->find($taskId);

        $this->assertEquals('Strictly Updated Title',
            $updatedTask->getTitle()
        );
        $this->assertEquals($originalUser->getId(),
            $updatedTask->getUser()->getId(),
            'The task user ID was altered or cleared during the update process.'
        );
    }

}
