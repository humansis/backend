<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;
use Entity\Helper\StandardizedPrimaryKey;

/**
 * UserProject
 */
#[ORM\Table(name: 'user_project')]
#[ORM\Entity(repositoryClass: 'Repository\UserProjectRepository')]
class UserProject
{
    use StandardizedPrimaryKey;

    #[ORM\ManyToOne(targetEntity: 'Entity\User', cascade: ['persist'], inversedBy: 'projects')]
    private ?\Entity\User $user = null;

    #[ORM\ManyToOne(targetEntity: 'Entity\Project', inversedBy: 'usersProject')]
    private ?\Entity\Project $project = null;

    #[ORM\Column(name: 'rights', type: 'string')]
    private string $rights;

    /**
     * Set rights.
     *
     * @param string $rights
     *
     * @return UserProject
     */
    public function setRights(string $rights): UserProject
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
     * @return UserProject
     */
    public function setUser(?User $user = null): UserProject
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

    /**
     * Set project.
     *
     * @param Project|null $project
     *
     * @return UserProject
     */
    public function setProject(?Project $project = null): UserProject
    {
        $this->project = $project;

        return $this;
    }

    /**
     * Get project.
     *
     * @return Project|null
     */
    public function getProject(): ?Project
    {
        return $this->project;
    }
}
