<?php

namespace Tests\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class TaskControllerTest extends WebTestCase
{
    /**
     * Test creating a task successfully
     */
    public function testCreateTaskSuccess()
    {
        // 1. Create a single client
        $client = static::createClient();
        $container = $client->getContainer();

        // 2. Authenticate the client via Session Token (bypass form login)
        $em = $container->get('doctrine')->getManager();
        $user = $em->getRepository('AppBundle:User')->findOneBy(['username' => 'Mike']);

        if (!$user) {
            $this->fail("L'utilisateur de test 'Mike' n'existe pas dans la base de données.");
        }

        $session = $container->get('session');
        $firewallContext = 'main'; // Nom de ton pare-feu dans app/config/security.yml

        $token = new UsernamePasswordToken($user, null, $firewallContext, $user->getRoles());
        $session->set('_security_' . $firewallContext, serialize($token));
        $session->save();

        $cookie = new Cookie($session->getName(), $session->getId());
        $client->getCookieJar()->set($cookie);

        // 3. Request the creation page using the router
        $crawler = $client->request('GET', $container->get('router')->generate('task_create'));

        // 4. Verify page is accessible (HTTP 200)
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        // 5. Select and fill the form
        $form = $crawler->selectButton('Ajouter la tâche')->form();

        $form['task[title]'] = 'New Task From PHPUnit';
        $form['task[content]'] = 'Testing automated task submission.';

        // 6. Submit form
        $client->submit($form);

        // 7. Verify redirection (HTTP 302)
        $this->assertEquals(302, $client->getResponse()->getStatusCode());

        // 8. Follow redirection and verify success message
        $crawler = $client->followRedirect();

        $this->assertGreaterThan(
            0,
            $crawler->filter('.alert-success')->count(),
            'Expected a success flash message to be displayed.'
        );
    }
}
