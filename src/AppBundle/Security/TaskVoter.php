<?php

namespace AppBundle\Security;

use AppBundle\Entity\Task;
use AppBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

/**
 * Class TaskVoter
 *
 * Validates if a user has the authorization to perform specific actions on a Task.
 * Primarily checks if the logged-in user is the author of the task before deletion.
 *
 * @package AppBundle\Security
 */
class TaskVoter extends Voter
{
    const DELETE = 'delete';

    /**
     * @var AccessDecisionManagerInterface
     */
    private $decisionManager;

    /**
     * TaskVoter constructor.
     *
     * @param AccessDecisionManagerInterface $decisionManager
     */
    public function __construct(AccessDecisionManagerInterface $decisionManager)
    {
        $this->decisionManager = $decisionManager;
    }

    /**
     * Determines if the attribute and subject are supported by this voter.
     *
     * @param string $attribute
     * @param mixed $subject
     * @return bool
     */
    protected function supports($attribute, $subject)
    {
        // If the attribute isn't "delete", we don't support it
        if ($attribute !== self::DELETE) {
            return false;
        }

        // Only vote on Task objects
        if (($subject instanceof Task) === false) {
            return false;
        }

        return true;
    }

    /**
     * Perform a single access check operation on a given attribute, subject and token.
     *
     * @param string $attribute
     * @param Task $subject
     * @param TokenInterface $token
     * @return bool
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        $user = $token->getUser();

        // If the user is not logged in, deny access
        if (($user instanceof User) === false) {
            return false;
        }

        switch ($attribute) {
            case self::DELETE:
                return $this->canDelete($subject, $user, $token);
        }

        return false;
    }

    /**
     * Check if the user is allowed to delete the task.
     *
     * @param Task $task
     * @param User $user
     * @param TokenInterface $token
     * @return bool
     */
    private function canDelete(Task $task, User $user, TokenInterface $token)
    {
        $author = $task->getUser();

        // The logged-in user must be the strict author of the task.
        return $user->getId() === $author->getId();
    }
}
