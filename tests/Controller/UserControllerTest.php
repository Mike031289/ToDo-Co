<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class UserControllerTest
 *
 * Test suite for user management endpoints in Symfony 5.4.
 *
 * @package App\Tests\Controller
 * @covers \App\Controller\UserController
 */
class UserControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    /**
     * Helper to create an authenticated admin client.
     */
    private function loginAsAdmin(): void
    {
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $userRepository = $em->getRepository(User::class);

        $admin = $userRepository->findOneBy(['username' => 'AdminUserControllerTest']);

        if (null === $admin) {
            $admin = new User();
            $admin->setUsername('AdminUserControllerTest');
            $admin->setEmail('admin_user_controller_test@example.com');
            $admin->setRoles(['ROLE_ADMIN']);

            $hasher = $container->get('security.user_password_hasher');
            $admin->setPassword($hasher->hashPassword($admin, 'adminpassword123'));

            $em->persist($admin);
            $em->flush();
        }

        $this->client->loginUser($admin);
    }

    public function testListUsers(): void
    {
        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', '/users');

        $this->assertResponseIsSuccessful();
        $this->assertGreaterThan(0, $crawler->filter('html:contains("Liste des utilisateurs")')->count());
    }

    public function testCreateUserSuccess(): void
    {
        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', '/users/create');

        $this->assertResponseIsSuccessful();

        $form = $crawler->filter('form')->form([
            'user[username]'         => 'NewUser_' . uniqid(),
            'user[password][first]'  => 'TestPassword123!',
            'user[password][second]' => 'TestPassword123!',
            'user[email]'            => 'newuser_' . uniqid() . '@example.com',
            'user[roles]'            => 'ROLE_USER',
        ]);

        $this->client->submit($form);
        $this->assertResponseRedirects('/users');

        $this->client->followRedirect();
        $this->assertSelectorExists('.alert-success', "La confirmation de création est absente.");
    }

    public function testEditUser(): void
    {
        $this->loginAsAdmin();
        $em = static::getContainer()->get('doctrine')->getManager();
        $user = $em->getRepository(User::class)->findOneBy(['username' => 'AdminUserControllerTest']);

        $crawler = $this->client->request('GET', sprintf('/users/%d/edit', $user->getId()));
        $this->assertResponseIsSuccessful();

        $form = $crawler->filter('form')->form([
            'user[username]'         => 'AdminUpdated',
            'user[email]'            => 'updated_admin@example.com',
            'user[roles]'            => 'ROLE_ADMIN',
        ]);

        $this->client->submit($form);
        $this->assertResponseRedirects('/users');
    }
}
