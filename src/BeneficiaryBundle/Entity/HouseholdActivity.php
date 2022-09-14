<?php

namespace BeneficiaryBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use NewApiBundle\Entity\AbstractEntity;
use UserBundle\Entity\User;

/**
 * Household activity.
 *
 * @ORM\Table(name="household_activity")
 * @ORM\Entity(repositoryClass="BeneficiaryBundle\Repository\HouseholdActivityRepository")
 */
class HouseholdActivity extends AbstractEntity
{

    /**
     * @var Household
     *
     * @ORM\ManyToOne(targetEntity="BeneficiaryBundle\Entity\Household")
     */
    private $household;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="UserBundle\Entity\User")
     */
    private $author;

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="json")
     */
    private $content;

    /**
     * @var \DateTimeInterface
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    private $createdAt;

    public function __construct(Household $household, ?User $author, string $content)
    {
        $this->household = $household;
        $this->author = $author;
        $this->content = $content;
        $this->createdAt = new \DateTime('now');
    }


    /**
     * @return Household
     */
    public function getHousehold(): Household
    {
        return $this->household;
    }

    /**
     * @return User|null
     */
    public function getAuthor(): ?User
    {
        return $this->author;
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }
}
