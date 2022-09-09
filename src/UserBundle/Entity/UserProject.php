<?php

namespace UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use NewApiBundle\Entity\AbstractEntity;
use ProjectBundle\Entity\Project;
use Symfony\Component\Serializer\Annotation\Groups as SymfonyGroups;

/**
 * UserProject
 *
 * @ORM\Table(name="user_project")
 * @ORM\Entity(repositoryClass="UserBundle\Repository\UserProjectRepository")
 * @ORM\HasLifecycleCallbacks
 */
class UserProject extends AbstractEntity
{
    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="UserBundle\Entity\User", inversedBy="projects", cascade={"persist"})
     */
    private $user;

    /**
     * @var Project
     *
     * @ORM\ManyToOne(targetEntity="ProjectBundle\Entity\Project", inversedBy="usersProject")
     * @SymfonyGroups({"FullUser"})
     */
    private $project;

    /**
     * @var string
     *
     * @ORM\Column(name="rights", type="string")
     */
    private $rights;


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
