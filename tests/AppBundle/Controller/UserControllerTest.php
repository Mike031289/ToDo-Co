<?php

namespace Tests\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use AppBundle\Entity\User;

class UserControllerTest extends WebTestCase
{
    /**
     * Test that an administrator can update another user's role to ROLE_ADMIN.
     *
     * @return void
     */
    public function testAdminCanChangeUserRole()
    {
        // 1. Simulate logging in as an admin
        $client = static::createClient([], [
            'PHP_AUTH_USER' => 'Mike',
            'PHP_AUTH_PW'   => 'password123',
        ]);

        $container = $client->getContainer();
        $em = $container->get('doctrine')->getManager();

        // 2. Retrieve the user we want to modify (SimpleUser)
        /** @var User $userToModify */
        $userToModify = $em->getRepository('AppBundle:User')->findOneBy(['username' => 'Mike']);
        $this->assertNotNull($userToModify, 'The fixture user "SimpleUser" is missing.');

        // 3. Request the edit page
        $crawler = $client->request('GET', '/users/' . $userToModify->getId() . '/edit');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        // 4. Select and submit the form, shifting the role to ROLE_ADMIN
        $form = $crawler->selectButton('Modifier')->form();

        // For choice fields use assignment. If the field accepts multiple values provide an array.
        $form['user[roles]'] = 'ROLE_ADMIN';

        $client->submit($form);

        // 5. Assert successful redirect to the list page
        $this->assertEquals(302, $client->getResponse()->getStatusCode());
        $client->followRedirect();
        $this->assertContains("utilisateur a bien été modifié", $client->getResponse()->getContent());

        // 6. Force Doctrine to fetch updated data from the database
        $em->clear();
        $updatedUser = $em->getRepository('AppBundle:User')->find($userToModify->getId());

        // 7. Assert that the role has been successfully modified in DB
        $this->assertContains('ROLE_ADMIN', $updatedUser->getRoles());
    }
}
