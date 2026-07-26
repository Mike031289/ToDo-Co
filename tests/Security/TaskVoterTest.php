<?php

namespace Tests\App\Security;

use App\Entity\Task;
use App\Entity\User;
use App\Security\TaskVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * Class TaskVoterTest
 *
 * Performs unit testing on TaskVoter to validate permission evaluation,
 * anonymous task deletion restrictions, and standard ownership conditions.
 *
 * @package Tests\App\Security
 */
class TaskVoterTest extends TestCase
{
    /**
     * @var AccessDecisionManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $decisionManagerMock;

    /**
     * @var TokenInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $tokenMock;

    /**
     * @var TaskVoter
     */
    private $voter;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->decisionManagerMock = $this->createMock(AccessDecisionManagerInterface::class);
        $this->tokenMock = $this->createMock(TokenInterface::class);
        $this->voter = new TaskVoter($this->decisionManagerMock);
    }

    /**
     * Verify that the voter abstains (returns 0) when an unsupported attribute or subject is provided.
     *
     * @return void
     */
    public function testVoterAbstainsOnUnsupportedAttributeOrSubject()
    {
        // Case 1: Unsupported attribute, valid subject
        $this->assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($this->tokenMock, new Task(), ['VIEW'])
        );

        // Case 2: Supported attribute, invalid subject structure
        $this->assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($this->tokenMock, new \stdClass(), ['delete'])
        );
    }

    /**
     * Verify that access is denied if there is no authenticated User object inside the token.
     *
     * @return void
     */
    public function testVoteDeniesAccessWhenUserIsNotLoggedIn()
    {
        $this->tokenMock->method('getUser')->willReturn('anon.');

        $vote = $this->voter->vote($this->tokenMock, new Task(), ['delete']);
        $this->assertSame(VoterInterface::ACCESS_DENIED, $vote);
    }

    /**
     * Verify that an administrator can delete an anonymous task (without assigned user).
     *
     * @return void
     */
    public function testAdminCanDeleteAnonymousTaskWithNullAuthor()
    {
        $user = new User();
        $this->tokenMock->method('getUser')->willReturn($user);

        $task = new Task(); // author is null by default

        $this->decisionManagerMock->expects($this->once())
            ->method('decide')
            ->with($this->tokenMock, ['ROLE_ADMIN'])
            ->willReturn(true);

        $vote = $this->voter->vote($this->tokenMock, $task, ['delete']);
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $vote);
    }

    /**
     * Verify that an administrator can delete a task explicitly linked to an "anonyme" username string.
     *
     * @return void
     */
    public function testAdminCanDeleteAnonymousTaskWithAnonymeUsername()
    {
        $user = new User();
        $this->tokenMock->method('getUser')->willReturn($user);

        $anonymousUser = new User();
        $anonymousUser->setUsername('anonyme');

        $task = new Task();
        $task->setUser($anonymousUser);

        $this->decisionManagerMock->expects($this->once())
            ->method('decide')
            ->with($this->tokenMock, ['ROLE_ADMIN'])
            ->willReturn(true);

        $vote = $this->voter->vote($this->tokenMock, $task, ['delete']);
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $vote);
    }

    /**
     * Verify that a user can delete a task if they are the strict owner.
     *
     * @return void
     */
    public function testOwnerCanDeleteTask()
    {
        /** @var User|\PHPUnit\Framework\MockObject\MockObject $ownerMock */
        $ownerMock = $this->createMock(User::class);
        $ownerMock->method('getUsername')->willReturn('Jean');
        $ownerMock->method('getId')->willReturn(42);

        $this->tokenMock->method('getUser')->willReturn($ownerMock);

        $task = new Task();
        $task->setUser($ownerMock);

        $vote = $this->voter->vote($this->tokenMock, $task, ['delete']);
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $vote);
    }

    /**
     * Verify that a user cannot delete a task owned by someone else.
     *
     * @return void
     */
    public function testNonOwnerCannotDeleteTask()
    {
        /** @var User|\PHPUnit\Framework\MockObject\MockObject $currentUserMock */
        $currentUserMock = $this->createMock(User::class);
        $currentUserMock->method('getUsername')->willReturn('Jean');
        $currentUserMock->method('getId')->willReturn(42);

        /** @var User|\PHPUnit\Framework\MockObject\MockObject $otherUserMock */
        $otherUserMock = $this->createMock(User::class);
        $otherUserMock->method('getUsername')->willReturn('Pierre');
        $otherUserMock->method('getId')->willReturn(99);

        $this->tokenMock->method('getUser')->willReturn($currentUserMock);

        $task = new Task();
        $task->setUser($otherUserMock);

        $vote = $this->voter->vote($this->tokenMock, $task, ['delete']);
        $this->assertSame(VoterInterface::ACCESS_DENIED, $vote);
    }

    /**
     * Force the voter internal fallback condition execution branch to hit 100% coverage.
     *
     * Uses reflection to call the protected voteOnAttribute method with a bypassed value
     * to ensure code coverage tool analysis satisfies the ultimate security return false statement.
     *
     * @return void
     */
    public function testVoteReturnsFalseOnUnsupportedAttributePassedDirectly()
    {
        $user = new User();
        $this->tokenMock->method('getUser')->willReturn($user);

        $reflection = new \ReflectionClass(TaskVoter::class);
        $method = $reflection->getMethod('voteOnAttribute');

        $result = $method->invokeArgs($this->voter, ['UNSUPPORTED_ATTRIBUTE', new Task(), $this->tokenMock]);
        $this->assertFalse($result);
    }
}
