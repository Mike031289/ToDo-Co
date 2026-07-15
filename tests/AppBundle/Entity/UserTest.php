<?php

namespace Tests\AppBundle\Entity;

use AppBundle\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    /**
     * Test that a newly created user has at least ROLE_USER by default.
     *
     * @return void
     */
    public function testDefaultRole()
    {
        $user = new User();

        $this->assertContains('ROLE_USER', $user->getRoles());
        $this->assertCount(1, $user->getRoles());
    }

    /**
     * Test that setting custom roles updates the array correctly and always includes ROLE_USER.
     *
     * @return void
     */
    public function testSetRoles()
    {
        $user = new User();
        $user->setRoles(['ROLE_ADMIN']);

        $this->assertContains('ROLE_ADMIN', $user->getRoles());
        $this->assertContains('ROLE_USER', $user->getRoles());
        $this->assertCount(2, $user->getRoles());
    }
}
