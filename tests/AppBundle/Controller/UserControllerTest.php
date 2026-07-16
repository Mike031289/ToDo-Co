<?php

namespace Tests\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class UserControllerTest
 *
 * Runs functional tests against the UserController routes.
 * Validates access control lists (ACL) ensuring user management remains restricted
 * to authorized administrative roles.
 *
 * @package Tests\AppBundle\Controller
 */
class UserControllerTest extends WebTestCase
{
    /**
     * Helper method to create an authenticated HTTP client.
     *
     * Simulates basic HTTP authentication headers to log in a user
     * before sending requests.
     *
     * @param string $username The username of the user to authenticate
     * @param string $password The plain-text password of the user
     * @return \Symfony\Bundle\FrameworkBundle\Client An authenticated browser-like client instance
     */
    private function createAuthenticatedClient($username, $password)
    {
        return static::createClient([], [
            'PHP_AUTH_USER' => $username,
            'PHP_AUTH_PW'   => $password,
        ]);
    }

    /**
     * Test that a standard user (ROLE_USER) is restricted from accessing admin routes.
     *
     * Validates that accessing '/users' and '/users/create' yields a 403 Forbidden
     * HTTP status code when requested by unauthorized accounts.
     *
     * @return void
     */
    public function testSimpleUserCannotAccessUserManagement()
    {
        // 1. Arrange: Authenticate as a regular user (ROLE_USER)
        $client = $this->createAuthenticatedClient('JohnDoe', 'password123');

        // 2. Act & Assert: Attempt to browse the user list page
        $client->request('GET', '/users');
        $this->assertEquals(403, $client->getResponse()->getStatusCode());

        // 3. Act & Assert: Attempt to reach the user creation form
        $client->request('GET', '/users/create');
        $this->assertEquals(403, $client->getResponse()->getStatusCode());
    }

    /**
     * Test that an administrator (ROLE_ADMIN) can successfully manage users.
     *
     * Validates that accessing '/users' yields a 200 OK HTTP status code
     * when the client holds the required administrative credentials.
     *
     * @return void
     */
    public function testAdminCanAccessUserList()
    {
        // 1. Arrange: Authenticate as an admin user (ROLE_ADMIN)
        $client = $this->createAuthenticatedClient('Mike', 'password123');

        // 2. Act: Query the restricted user list route
        $client->request('GET', '/users');

        // 3. Assert: Verify the page loads successfully
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }
}
