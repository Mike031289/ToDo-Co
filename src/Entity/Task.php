<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="task")
 */
class Task
{
    /**
     * @var int|null
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private ?int $id = null;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     */
    private \DateTime $createdAt;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank(message="Vous devez saisir un titre.")
     * @Assert\Length(
     *    max = 255,
     *    maxMessage = "Le titre ne peut pas dépasser {{ limit }} caractères pour éviter la saturation."
     * )
     */
    private ?string $title = null;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text")
     * @Assert\NotBlank(message="Vous devez saisir du contenu.")
     * @Assert\Length(
     *    max = 10000,
     *    maxMessage = "Le contenu est trop long (maximum {{ limit }} caractères)."
     * )
     */
    private ?string $content = null;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private bool $isDone;

    /**
     * @var \App\Entity\User|null
     *
     * @ORM\ManyToOne(targetEntity="User", inversedBy="tasks")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=true)
     */
    private ?User $user = null;

    /**
     * Task constructor.
     */
    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->isDone = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = strip_tags(trim($title));

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = strip_tags(trim($content));

        return $this;
    }

    public function isDone(): bool
    {
        return $this->isDone;
    }

    public function setIsDone(bool $isDone): self
    {
        $this->isDone = $isDone;

        return $this;
    }

    public function toggle(bool $flag): self
    {
        $this->isDone = $flag;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }
}
