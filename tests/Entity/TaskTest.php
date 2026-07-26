<?php

namespace Tests\App\Entity;

use App\Entity\Task;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

/**
 * Class TaskTest
 *
 * Performs unit testing on the Task entity to validate internal logic,
 * default values, and relational mapping isolated from database layers.
 *
 * @package Tests\App\Entity
 */
class TaskTest extends TestCase
{
    /**
     * @var Task The isolated Task entity instance under test
     */
    private $task;

    /**
     * Set up the test environment before each test execution.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->task = new Task();
    }

    /**
     * Verify default values assigned automatically during instantiation.
     *
     * @return void
     */
    public function testDefaultValues()
    {
        $this->assertInstanceOf(\DateTime::class, $this->task->getCreatedAt());
        $this->assertFalse($this->task->isDone());
        $this->assertNull($this->task->getUser());
        $this->assertNull($this->task->getId()); // ID is null until persisted
    }

    /**
     * Verify Getters and Setters behavior for basic task fields.
     *
     * @return void
     */
    public function testGettersAndSetters()
    {
        $now = new \DateTime();

        $this->task->setTitle('Faire la vaisselle');
        $this->task->setContent('Laver les assiettes et les verres.');
        $this->task->setCreatedAt($now);
        $this->task->toggle(true);

        $this->assertSame('Faire la vaisselle', $this->task->getTitle());
        $this->assertSame('Laver les assiettes et les verres.', $this->task->getContent());
        $this->assertSame($now, $this->task->getCreatedAt());
        $this->assertTrue($this->task->isDone());
    }

    /**
     * Verify the User relationship mapping logic.
     *
     * Tests both the attachment of an owner entity and the complete detachment
     * to safely clear the relational tree mapping.
     *
     * @return void
     */
    public function testTaskUserRelation()
    {
        // Create a real instance of User to test the actual operational method mapping
        $user = new User();
        $user->setUsername('Jean');

        $this->task->setUser($user);

        $this->assertInstanceOf(User::class, $this->task->getUser());
        $this->assertSame('Jean', $this->task->getUser()->getUsername());

        // Revert relation mapping back to null to evaluate the boundary branch inside the setter
        $this->task->setUser(null);
        $this->assertNull($this->task->getUser());
    }

    /**
     * Test the setIsDone setter and toggle behaviors directly on the entity.
     */
    public function testSetIsDone()
    {
        $task = new Task();

        // Par défaut c'est faux, on force à true
        $task->setIsDone(true);
        $this->assertTrue($task->isDone());

        // On rebascule à false
        $task->setIsDone(false);
        $this->assertFalse($task->isDone());
    }
}
