<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;
use Entity\Helper\CountryDependent;
use Entity\Helper\StandardizedPrimaryKey;

/**
 * UserCountry
 *
 * @ORM\Table(name="user_country")
 * @ORM\Entity(repositoryClass="Repository\UserCountryRepository")
 */
class UserCountry
{
    use CountryDependent;
    use StandardizedPrimaryKey;

    /**
     * @var string
     *
     * @ORM\Column(name="rights", type="string")
     */
    private $rights;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Entity\User", inversedBy="countries", cascade={"persist"})
     */
    private $user;

    /**
     * Set rights.
     *
     * @param string $rights
     *
     * @return UserCountry
     */
    public function setRights(string $rights): UserCountry
    {
        $this->rights = $rights;

        return $this;
    }

    /**
     * Get rights.
     *
     * @return string
     */
    public function getRights(): string
    {
        return $this->rights;
    }

    /**
     * Set user.
     *
     * @param User|null $user
     *
     * @return UserCountry
     */
    public function setUser(User $user = null): UserCountry
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user.
     *
     * @return User|null
     */
    public function getUser(): ?User
    {
        return $this->user;
    }
}
