<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class SecurityControllerTest
 *
 * Functional tests for authentication, login security flows, and user session management.
 */
class SecurityControllerTest extends WebTestCase
{
    /**
     * Test successful user authentication.
     */
    public function testLoginSuccess(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Se connecter')->form([
            '_username' => 'mike@example.com', // Using the email configured in fixtures
            '_password' => 'password123',
        ]);

        $client->submit($form);

        // Verify redirection after successful login (e.g., to the homepage)
        $this->assertResponseRedirects();
    }

    /**
     * Test failed user authentication with invalid credentials.
     */
    public function testLoginFailure(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        $form = $crawler->selectButton('Se connecter')->form();

        $form['_username'] = 'mike@example.com';
        $form['_password'] = 'mauvais_mdp';

        $client->submit($form);

        // After failure, verify redirection back to the login page
        $this->assertResponseRedirects();
        $client->followRedirect();
    }

    /**
     * Test user logout functionality.
     */
    public function testLogout(): void
    {
        $client = static::createClient();

        $userRepository = static::getContainer()
            ->get('doctrine')
            ->getRepository(User::class);

        $user = $userRepository->findOneBy([
            'email' => 'mike@example.com' // Lookup by email (standard Symfony security property)
        ]);

        $this->assertNotNull($user, 'The test user "Mike" must exist in the database via fixtures.');

        $client->loginUser($user);

        $client->request('GET', '/logout');

        $this->assertResponseRedirects();
    }
}
