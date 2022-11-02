<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;
use Entity\Project;

/**
 * UserProject
 *
 * @ORM\Table(name="user_project")
 * @ORM\Entity(repositoryClass="Repository\UserProjectRepository")
 */
class UserProject
{
    /**
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private int $id;

    /**
     * @ORM\ManyToOne(targetEntity="Entity\User", inversedBy="projects", cascade={"persist"})
     */
    private ?\Entity\User $user = null;

    /**
     * @ORM\ManyToOne(targetEntity="Entity\Project", inversedBy="usersProject")
     */
    private ?\Entity\Project $project = null;

    /**
     * @ORM\Column(name="rights", type="string")
     */
    private string $rights;

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
     * @param User|null $user
     *
     * @return UserProject
     */
    public function setUser(User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user.
     *
     * @return User|null
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set project.
     *
     * @param \Entity\Project|null $project
     *
     * @return UserProject
     */
    public function setProject(\Entity\Project $project = null)
    {
        $this->project = $project;

        return $this;
    }

    /**
     * Get project.
     *
     * @return \Entity\Project|null
     */
    public function getProject()
    {
        return $this->project;
    }
}
