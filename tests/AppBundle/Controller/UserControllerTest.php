<?php

namespace Tests\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use AppBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\BrowserKit\Cookie;

/**
 * Class UserControllerTest
 *
 * @package Tests\AppBundle\Controller
 * @covers \AppBundle\Controller\UserController
 */
class UserControllerTest extends WebTestCase
{
    /**
     * Helper to create an authenticated client using session storage simulation.
     *
     * @return \Symfony\Bundle\FrameworkBundle\Client
     */
    private function createAuthenticatedAdminClient()
    {
        $client = static::createClient();
        $container = $client->getContainer();
        $session = $container->get('session');
        $em = $container->get('doctrine')->getManager();

        /** @var User $admin */
        $admin = $em->getRepository(User::class)->findOneBy(['username' => 'AdminUserControllerTest']);

        // Safe fallback in case database was cleared before test execution
        if (null === $admin) {
            $admin = new User();
            $admin->setUsername('AdminUserControllerTest');
            $admin->setEmail('admin_user_controller_test@example.com');
            $admin->setRoles(['ROLE_ADMIN']);

            $encoder = $container->get('security.password_encoder');
            $hashedPassword = $encoder->encodePassword($admin, 'adminpassword123');
            $admin->setPassword($hashedPassword);

            $em->persist($admin);
            $em->flush();
        }

        // Define firewall context name (must match your main firewall key in security.yml, usually 'main')
        $firewallContext = 'main';

        $token = new UsernamePasswordToken($admin, null, $firewallContext, $admin->getRoles());
        $session->set('_security_' . $firewallContext, serialize($token));
        $session->save();

        $cookie = new Cookie($session->getName(), $session->getId());
        $client->getCookieJar()->set($cookie);

        return $client;
    }

    /**
     * Set up an administrative user inside the test isolation context database with an encoded password.
     */
    protected function setUp()
    {
        $client = static::createClient();
        $container = $client->getContainer();
        $em = $container->get('doctrine')->getManager();

        $admin = $em->getRepository(User::class)->findOneBy(['username' => 'AdminUserControllerTest']);

        if (null === $admin) {
            $admin = new User();
            $admin->setUsername('AdminUserControllerTest');
            $admin->setEmail('admin_user_controller_test@example.com');
            $admin->setRoles(['ROLE_ADMIN']);

            $encoder = $container->get('security.password_encoder');
            $hashedPassword = $encoder->encodePassword($admin, 'adminpassword123');
            $admin->setPassword($hashedPassword);

            $em->persist($admin);
            $em->flush();
        }
    }

    /**
     * Test displaying the users management list layout.
     */
    public function testListUsers()
    {
        $client = $this->createAuthenticatedAdminClient();
        $crawler = $client->request('GET', '/users');

        $this->assertSame(200, $client->getResponse()->getStatusCode(), "FAILED: The route /users does not return a 200 OK.");
        $this->assertGreaterThan(0, $crawler->filter('html:contains("Liste des utilisateurs")')->count());
    }

    /**
     * Test successful creation of a new user entity through form handler.
     */
    public function testCreateUserSuccess()
    {
        $client = $this->createAuthenticatedAdminClient();
        $crawler = $client->request('GET', '/users/create');

        $this->assertSame(200, $client->getResponse()->getStatusCode(), "FAILED: Route /users/create is inaccessible.");

        $form = $crawler->filter('form')->form([
            'user[username]'         => 'NewUser_' . uniqid(),
            'user[password][first]'  => 'TestPassword123!',
            'user[password][second]' => 'TestPassword123!',
            'user[email]'            => 'newuser_' . uniqid() . '@example.com',
            'user[roles]'            => 'ROLE_USER',
        ]);

        $client->submit($form);
        $this->assertTrue($client->getResponse()->isRedirect(), "FAILED: User creation did not trigger a redirect response status.");

        $crawler = $client->followRedirect();

        $this->assertGreaterThan(
            0,
            $crawler->filter('.alert-success:contains("L\'utilisateur a bien été ajouté.")')->count(),
            "FAILED: Flash message de confirmation d'ajout introuvable ou mal orthographié."
        );
    }

    /**
     * Test editing an existing user workflow parameters.
     */
    public function testEditUser()
    {
        $client = $this->createAuthenticatedAdminClient();
        $em = $client->getContainer()->get('doctrine')->getManager();

        /** @var User $user */
        $user = $em->getRepository(User::class)->findOneBy(['username' => 'AdminUserControllerTest']);

        $this->assertNotNull($user, "FAILED: No user found in the database to run the edit test.");

        $crawler = $client->request('GET', sprintf('/users/%d/edit', $user->getId()));
        $this->assertSame(200, $client->getResponse()->getStatusCode(), "FAILED: Edit user route returned an error code.");

        $form = $crawler->filter('form')->form([
            'user[username]'         => 'AdminUserControllerTest',
            'user[password][first]'  => 'adminpassword123',
            'user[password][second]' => 'adminpassword123',
            'user[email]'            => 'updated_admin@example.com',
            'user[roles]'            => 'ROLE_ADMIN',
        ]);

        $client->submit($form);
        $this->assertTrue($client->getResponse()->isRedirect());

        $crawler = $client->followRedirect();

        $this->assertGreaterThan(
            0,
            $crawler->filter('.alert-success:contains("L\'utilisateur a bien été modifié")')->count(),
            "FAILED: Flash message de confirmation de modification introuvable ou mal orthographié."
        );
    }

    /**
     * Test editing an existing user without changing their password.
     * This ensures the fallback logic preserves the old password and hits the uncovered else branch.
     */
    public function testEditUserKeepExistingPassword()
    {
        $client = $this->createAuthenticatedAdminClient();
        $em = $client->getContainer()->get('doctrine')->getManager();

        /** @var User $user */
        $user = $em->getRepository(User::class)->findOneBy(['username' => 'AdminUserControllerTest']);

        $this->assertNotNull($user, "FAILED: No user found in the database to run the edit password fallback test.");

        $crawler = $client->request('GET', sprintf('/users/%d/edit', $user->getId()));
        $this->assertSame(200, $client->getResponse()->getStatusCode());

        // Leave password fields empty to trigger the internal controller else branch
        $form = $crawler->filter('form')->form([
            'user[username]'         => 'AdminUserControllerTest',
            'user[password][first]'  => '',
            'user[password][second]' => '',
            'user[email]'            => 'another_update@example.com',
            'user[roles]'            => 'ROLE_ADMIN',
        ]);

        $client->submit($form);
        $this->assertTrue($client->getResponse()->isRedirect());

        $crawler = $client->followRedirect();

        $this->assertGreaterThan(
            0,
            $crawler->filter('.alert-success:contains("L\'utilisateur a bien été modifié")')->count()
        );
    }

    /**
     * Test form submission failure to hit the final uncovered HTML rendering branches.
     */
    public function testCreateUserFormValidationFailure()
    {
        $client = $this->createAuthenticatedAdminClient();
        $crawler = $client->request('GET', '/users/create');

        // Submit mismatched passwords to force a validation failure branch response
        $form = $crawler->filter('form')->form([
            'user[username]'         => 'InvalidUser',
            'user[password][first]'  => 'password123',
            'user[password][second]' => 'differentpassword456',
            'user[email]'            => 'invaliduser@example.com',
            'user[roles]'            => 'ROLE_USER',
        ]);

        $crawler = $client->submit($form);

        // Should return a 200 OK containing form errors instead of a 302 redirect
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertFalse($client->getResponse()->isRedirect());
    }
}
