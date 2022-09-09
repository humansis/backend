<?php

namespace UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use NewApiBundle\Entity\Helper\CountryDependent;

/**
 * UserCountry
 *
 * @ORM\Table(name="user_country")
 * @ORM\Entity(repositoryClass="UserBundle\Repository\UserCountryRepository")
 */
class UserCountry
{
    use CountryDependent;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="rights", type="string")
     */
    private $rights;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="UserBundle\Entity\User", inversedBy="countries", cascade={"persist"})
     */
    private $user;


    /**
     * Get id.
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

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
