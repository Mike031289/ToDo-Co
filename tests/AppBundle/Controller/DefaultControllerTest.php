<?php

namespace Tests\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DefaultControllerTest extends WebTestCase
{
    public function testIndex()
    {
        // 1. Initialize the client with HTTP Basic Auth credentials using the test admin user
        $client = static::createClient([], [
            'PHP_AUTH_USER' => 'Mike',
            'PHP_AUTH_PW'   => 'password123',
        ]);

        // 2. Execute a GET request to the homepage
        $crawler = $client->request('GET', '/');

        // 3. Assert that the response status code is 200 OK
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        // 4. Assert that the page content contains at least one <h1> element
        $this->assertGreaterThan(0, $crawler->filter('h1')->count());
    }
}
