<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class SecurityControllerTest
 */
class SecurityControllerTest extends WebTestCase
{
    /**
     * Test de connexion réussie.
     */
    public function testLoginSuccess(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Se connecter')->form([
            '_username' => 'Mike',
            '_password' => 'password123',
        ]);

        $client->submit($form);

        // Vérification de la redirection après succès (ex: vers homepage)
        $this->assertResponseRedirects();
    }

    /**
     * Test de connexion échouée (mauvais mot de passe).
     */
    public function testLoginFailure(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        $form = $crawler->selectButton('Se connecter')->form();

        $form['_username'] = 'Mike';
        $form['_password'] = 'mauvais_mdp';

        $client->submit($form);

        // Après un échec, on reste sur la page de login
        // $this->assertSelectorExists('.alert-danger');
        $this->assertResponseRedirects();
        $client->followRedirect();

    }

    /**
     * Test de déconnexion.
     */
    public function testLogout(): void
    {
        $client = static::createClient();

        $userRepository = static::getContainer()
            ->get('doctrine')
            ->getRepository(User::class);

        $user = $userRepository->findOneBy([
            'username' => 'Mike'
        ]);

        $client->loginUser($user);

        $client->request('GET', '/logout');

        $this->assertResponseRedirects();
    }
}
