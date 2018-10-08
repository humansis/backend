<?php

namespace UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use ProjectBundle\Entity\Project;

/**
 * UserProject
 *
 * @ORM\Table(name="user_project")
 * @ORM\Entity(repositoryClass="UserBundle\Repository\UserProjectRepository")
 */
class UserProject
{

    const RIGHT_ADMIN = 3;
    const RIGHT_MANAGER = 2;
    const RIGHT_USER = 1;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="UserBundle\Entity\User", inversedBy="userProjects", cascade={"persist"})
     */
    private $user;

    /**
     * @var Project
     *
     * @ORM\ManyToOne(targetEntity="ProjectBundle\Entity\Project", inversedBy="usersProject")
     */
    private $project;

    /**
     * @var int
     *
     * @ORM\Column(name="rights", type="integer")
     */
    private $rights;

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
     * @param int $rights
     *
     * @return UserProject
     */
    public function setRights($rights)
    {
        $this->rights = $rights;

        return $this;
    }

    /**
     * Get rights.
     *
     * @return int
     */
    public function getRights()
    {
        return $this->rights;
    }

    /**
     * Set user.
     *
     * @param \UserBundle\Entity\User|null $user
     *
     * @return UserProject
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

    /**
     * Set project.
     *
     * @param \ProjectBundle\Entity\Project|null $project
     *
     * @return UserProject
     */
    public function setProject(\ProjectBundle\Entity\Project $project = null)
    {
        $this->project = $project;

        return $this;
    }

    /**
     * Get project.
     *
     * @return \ProjectBundle\Entity\Project|null
     */
    public function getProject()
    {
        return $this->project;
    }
}
