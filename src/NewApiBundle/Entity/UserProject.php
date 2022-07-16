<?php

namespace NewApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use NewApiBundle\Entity\Project;

/**
 * UserProject
 *
 * @ORM\Table(name="user_project")
 * @ORM\Entity(repositoryClass="NewApiBundle\Repository\UserProjectRepository")
 */
class UserProject
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
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="NewApiBundle\Entity\User", inversedBy="projects", cascade={"persist"})
     */
    private $user;

    /**
     * @var Project
     *
     * @ORM\ManyToOne(targetEntity="NewApiBundle\Entity\Project", inversedBy="usersProject")
     */
    private $project;

    /**
     * @var string
     *
     * @ORM\Column(name="rights", type="string")
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
     * @param string $rights
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
     * @return string
     */
    public function getRights()
    {
        return $this->rights;
    }

    /**
     * Set user.
     *
     * @param \NewApiBundle\Entity\User|null $user
     *
     * @return UserProject
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

    /**
     * Set project.
     *
     * @param \NewApiBundle\Entity\Project|null $project
     *
     * @return UserProject
     */
    public function setProject(\NewApiBundle\Entity\Project $project = null)
    {
        $this->project = $project;

        return $this;
    }

    /**
     * Get project.
     *
     * @return \NewApiBundle\Entity\Project|null
     */
    public function getProject()
    {
        return $this->project;
    }
}
