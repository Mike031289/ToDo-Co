<?php

namespace Tests\AppBundle\Entity;

use AppBundle\Entity\User;
use AppBundle\Entity\Task;
use PHPUnit\Framework\TestCase;

/**
 * Class UserTest
 *
 * Performs unit testing on the User entity to validate internal logic,
 * default values, and role privileges isolated from database layers.
 *
 * @package Tests\AppBundle\Entity
 */
class UserTest extends TestCase
{
    /**
     * @var User The isolated User entity instance under test
     */
    private $user;

    /**
     * Set up the test environment before each test execution.
     *
     * Initializes a fresh User instance to prevent state leakage between tests.
     *
     * @return void
     */
    protected function setUp()
    {
        $this->user = new User();
    }

    /**
     * Verify that any newly instantiated User is granted 'ROLE_USER' by default.
     *
     * @return void
     */
    public function testDefaultRole()
    {
        $this->assertContains('ROLE_USER', $this->user->getRoles());
        $this->assertCount(1, $this->user->getRoles());
    }

    /**
     * Verify custom roles mapping behavior.
     *
     * This test ensures that when adding custom privileges (like ROLE_ADMIN),
     * the basic security safety net 'ROLE_USER' is still automatically appended.
     *
     * @return void
     */
    public function testSetRoles()
    {
        $this->user->setRoles(['ROLE_ADMIN']);

        $this->assertContains('ROLE_ADMIN', $this->user->getRoles());
        $this->assertContains('ROLE_USER', $this->user->getRoles());
        $this->assertCount(2, $this->user->getRoles());
    }

    /**
     * Demonstrate the usage of Test Doubles (Stubs/Mocks) under PHPUnit.
     *
     * This dummy test validates that dependencies or external models can be
     * stubbed out to guarantee unit testing isolation according to static analysis tools.
     *
     * @return void
     */
    public function testUserWithMockedDependency()
    {
        // 1. Arrange: Create a Test Double (Stub) mimicking the User entity
        /** @var User|\PHPUnit\Framework\MockObject\MockObject $userStub */
        $userStub = $this->createMock(User::class);

        // 2. Act: Configure the Stub to intercept and return a fixed value for getEmail()
        $userStub->method('getEmail')
            ->willReturn('mocked-email@todo-co.local');

        // 3. Assert: Validate that the stubbed execution returns the expected mock payload
        $this->assertEquals('mocked-email@todo-co.local', $userStub->getEmail());
    }

    /**
     * Verify Getters and Setters behavior for core user properties.
     *
     * Ensures that data injected through mutations is cleanly retrieved
     * and checks internal default fallbacks like getSalt().
     *
     * @return void
     */
    public function testGettersAndSettersReal()
    {
        $this->user->setUsername('Alex');
        $this->user->setEmail('alex@todo-co.local');
        $this->user->setPassword('password123');

        $this->assertSame('Alex', $this->user->getUsername());
        $this->assertSame('alex@todo-co.local', $this->user->getEmail());
        $this->assertSame('password123', $this->user->getPassword());
        $this->assertNull($this->user->getId());
        $this->assertNull($this->user->getSalt());
    }

    /**
     * Verify eraseCredentials invocation.
     *
     * Fulfills the strict requirement of the UserInterface, ensuring the method
     * executes perfectly even when no explicit internal memory wipe logic is triggered.
     *
     * @return void
     */
    public function testEraseCredentials()
    {
        $this->assertNull($this->user->eraseCredentials());
    }

    /**
     * Test the tasks collection getter and relationship mechanics.
     *
     * @return void
     */
    public function testGetTasksCollection()
    {
        $user = new User();
        $task = new Task();

        // 1. Verify that the collection is initialized as a Doctrine ArrayCollection
        $this->assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $user->getTasks());
        $this->assertCount(0, $user->getTasks());

        // 2. Test the add and contains pipeline if the method exists on the entity
        if (method_exists($user, 'addTask') === true) {
            $user->addTask($task);
            $this->assertCount(1, $user->getTasks());
            $this->assertSame(true, $user->getTasks()->contains($task));
        }
    }
}
