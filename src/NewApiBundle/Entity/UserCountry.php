<?php

namespace NewApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserCountry
 *
 * @ORM\Table(name="user_country")
 * @ORM\Entity(repositoryClass="NewApiBundle\Repository\UserCountryRepository")
 */
class UserCountry
{
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
     * @var string
     *
     * @ORM\Column(name="iso3", type="string", length=3)
     */
    private $iso3;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="NewApiBundle\Entity\User", inversedBy="countries", cascade={"persist"})
     */
    private $user;


    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
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
    public function setRights($rights)
    {
        $this->rights = $rights;

        return $this;
    }

    /**
     * Get rights.
     *
     * @return string
     */
    public function getRights()
    {
        return $this->rights;
    }

    /**
     * Set iso3.
     *
     * @param string $iso3
     *
     * @return UserCountry
     */
    public function setIso3($iso3)
    {
        $this->iso3 = $iso3;

        return $this;
    }

    /**
     * Get iso3.
     *
     * @return string
     */
    public function getIso3()
    {
        return $this->iso3;
    }

    /**
     * Set user.
     *
     * @param \NewApiBundle\Entity\User|null $user
     *
     * @return UserCountry
     */
    public function setUser(\NewApiBundle\Entity\User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user.
     *
     * @return \NewApiBundle\Entity\User|null
     */
    public function getUser()
    {
        return $this->user;
    }
}
