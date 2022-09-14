<?php

namespace UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use NewApiBundle\Entity\AbstractEntity;
use Symfony\Component\Serializer\Annotation\Groups as SymfonyGroups;

/**
 * UserCountry
 *
 * @ORM\Table(name="user_country")
 * @ORM\Entity(repositoryClass="UserBundle\Repository\UserCountryRepository")
 */
class UserCountry extends AbstractEntity
{
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
     * @SymfonyGroups({"FullUser"})
     */
    private $iso3;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="UserBundle\Entity\User", inversedBy="countries", cascade={"persist"})
     */
    private $user;


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
     * @param \UserBundle\Entity\User|null $user
     *
     * @return UserCountry
     */
    public function setUser(\UserBundle\Entity\User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user.
     *
     * @return \UserBundle\Entity\User|null
     */
    public function getUser()
    {
        return $this->user;
    }
}
