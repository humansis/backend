<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;
use Entity\Helper\CountryDependent;

/**
 * UserCountry
 *
 * @ORM\Table(name="user_country")
 * @ORM\Entity(repositoryClass="Repository\UserCountryRepository")
 */
class UserCountry
{
    use CountryDependent;

    /**
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private ?int $id;

    /**
     * @ORM\Column(name="rights", type="string")
     */
    private ?string $rights = null;

    /**
     * @ORM\ManyToOne(targetEntity="Entity\User", inversedBy="countries", cascade={"persist"})
     */
    private ?\Entity\User $user = null;

    /**
     * Get id.
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set rights.
     *
     *
     */
    public function setRights(string $rights): UserCountry
    {
        $this->rights = $rights;

        return $this;
    }

    /**
     * Get rights.
     */
    public function getRights(): string
    {
        return $this->rights;
    }

    /**
     * Set user.
     *
     * @param User|null $user
     */
    public function setUser(User $user = null): UserCountry
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user.
     */
    public function getUser(): ?User
    {
        return $this->user;
    }
}
