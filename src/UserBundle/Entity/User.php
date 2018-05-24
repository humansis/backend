<?php

namespace UserBundle\Entity;

use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;

/**
 * User
 *
 * @ORM\Table(name="`user")
 * @ORM\Entity
 */
class User extends BaseUser
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\OneToMany(targetEntity="UserBundle\Entity\UserCountry", mappedBy="user")
     */
    private $countries;

    /**
     * @ORM\OneToMany(targetEntity="UserBundle\Entity\UserProject", mappedBy="user")
     */
    private $userProjects;



    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Add country.
     *
     * @param \UserBundle\Entity\UserCountry $country
     *
     * @return User
     */
    public function addCountry(\UserBundle\Entity\UserCountry $country)
    {
        $this->countries[] = $country;

        return $this;
    }

    /**
     * Remove country.
     *
     * @param \UserBundle\Entity\UserCountry $country
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeCountry(\UserBundle\Entity\UserCountry $country)
    {
        return $this->countries->removeElement($country);
    }

    /**
     * Get countries.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCountries()
    {
        return $this->countries;
    }

    /**
     * Add userProject.
     *
     * @param \UserBundle\Entity\UserProject $userProject
     *
     * @return User
     */
    public function addUserProject(\UserBundle\Entity\UserProject $userProject)
    {
        $this->userProjects[] = $userProject;

        return $this;
    }

    /**
     * Remove userProject.
     *
     * @param \UserBundle\Entity\UserProject $userProject
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeUserProject(\UserBundle\Entity\UserProject $userProject)
    {
        return $this->userProjects->removeElement($userProject);
    }

    /**
     * Get userProjects.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUserProjects()
    {
        return $this->userProjects;
    }
}
