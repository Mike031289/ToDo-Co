<?php

namespace Tests\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class DefaultControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $client = static::createClient();
        $container = $client->getContainer();

        // 1. Retrieve the entity manager to find the test user (e.g., Mike)
        $em = $container->get('doctrine')->getManager();
        $user = $em->getRepository('AppBundle:User')->findOneBy(['username' => 'Mike']);

        if (!$user) {
            $this->fail("The test user 'Mike' does not exist in the test database.");
        }

        // 2. Create a security Token and inject it into the session
        $session = $container->get('session');
        $firewallContext = 'main'; // Firewall name in your security.yml configuration (commonly 'main')

        $token = new UsernamePasswordToken($user, null, $firewallContext, $user->getRoles());
        $session->set('_security_'.$firewallContext, serialize($token));
        $session->save();

        // 3. Assign the session cookie to the client
        $cookie = new Cookie($session->getName(), $session->getId());
        $client->getCookieJar()->set($cookie);

        // 4. Send the request to the homepage
        $crawler = $client->request('GET', '/');

        // Verify the HTTP response status code is 200 OK
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertGreaterThan(0, $crawler->filter('h1')->count());
    }
}
