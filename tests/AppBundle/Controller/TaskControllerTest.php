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
}
