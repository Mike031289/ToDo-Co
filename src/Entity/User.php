<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * User Entity representing application accounts and security identities.
 *
 * @ORM\Table(name="user")
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 * @UniqueEntity(fields="email", message="Cette adresse email est déjà utilisée.")
 * @UniqueEntity(fields="username", message="Ce nom d'utilisateur est déjà utilisé.")
 */
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    /**
     * @var int|null The unique identifier for the user.
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private ?int $id = null;

    /**
     * @var string|null The unique username of the account.
     *
     * @ORM\Column(type="string", length=25, unique=true)
     * @Assert\NotBlank(message="Vous devez saisir un nom d'utilisateur.")
     */
    private ?string $username = null;

    /**
     * @var string|null The hashed user password.
     *
     * @ORM\Column(type="string", length=64)
     */
    private ?string $password = null;

    /**
     * @var string|null The unique email address for communication.
     *
     * @ORM\Column(type="string", length=60, unique=true)
     * @Assert\NotBlank(message="Vous devez saisir une adresse email.")
     * @Assert\Email(message="Le format de l'adresse n'est pas correcte.")
     */
    private ?string $email = null;

    /**
     * @var array List of security roles assigned to the user.
     *
     * @ORM\Column(type="json")
     */
    private array $roles = [];

    /**
     * @var Collection<int, Task> Collection of tasks created or assigned to this user.
     *
     * @ORM\OneToMany(targetEntity="Task", mappedBy="user")
     */
    private Collection $tasks;

    /**
     * User constructor initializing relational collections.
     */
    public function __construct()
    {
        $this->tasks = new ArrayCollection();
    }

    /**
     * Get the user identifier ID.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Get the username.
     */
    public function getUsername(): string
    {
        return $this->username ?? '';
    }

    /**
     * Set the username.
     */
    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    /**
     * A visual identifier representing this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    /**
     * Get the user's password.
     *
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * Set the user's password.
     */
    public function setPassword(?string $password): self
    {
        if (null !== $password) {
            $this->password = $password;
        }

        return $this;
    }

    /**
     * Returns the salt.
     *
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * Get the user email.
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * Set the user email.
     */
    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get assigned roles guarantees that every user has at least ROLE_USER.
     *
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;

        if (!in_array('ROLE_USER', $roles, true)) {
            $roles[] = 'ROLE_USER';
        }

        return array_unique($roles);
    }

    /**
     * Set assigned roles array.
     */
    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * Removes sensitive data from the user object.
     *
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
    }

    /**
     * Get all tasks associated with this user account.
     *
     * @return Collection<int, Task>
     */
    public function getTasks(): Collection
    {
        return $this->tasks;
    }

    /**
     * Add a task to this user.
     */
    public function addTask(Task $task): self
    {
        if (!$this->tasks->contains($task)) {
            $this->tasks->add($task);
            $task->setUser($this);
        }

        return $this;
    }

    /**
     * Remove a task from this user.
     */
    public function removeTask(Task $task): self
    {
        if ($this->tasks->removeElement($task)) {
            if ($task->getUser() === $this) {
                // Since user is mandatory (nullable=false), handling this requires caution
                // or letting Doctrine handle constraints depending on DB schema.
            }
        }

        return $this;
    }
}
