<?php

namespace Entity;

use DateTime;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Entity\Helper\StandardizedPrimaryKey;

/**
 * Household activity.
 */
#[ORM\Table(name: 'household_activity')]
#[ORM\Entity(repositoryClass: 'Repository\HouseholdActivityRepository')]
class HouseholdActivity
{
    use StandardizedPrimaryKey;

    #[ORM\ManyToOne(targetEntity: 'Entity\Household')]
    private Household $household;

    #[ORM\ManyToOne(targetEntity: 'Entity\User')]
    private ?User $author;

    #[ORM\Column(name: 'content', type: 'json')]
    private string $content;

    #[ORM\Column(name: 'created_at', type: 'datetime', nullable: false)]
    private \DateTimeInterface $createdAt;

    public function __construct(
        Household $household,
        ?User $author,
        string $content,
    ) {
        $this->household = $household;
        $this->author = $author;
        $this->content = $content;
        $this->createdAt = new DateTime('now');
    }

    public function getHousehold(): Household
    {
        return $this->household;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }
}
