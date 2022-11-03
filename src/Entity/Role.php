<?php

declare(strict_types=1);

namespace Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Entity\User;

/**
 * @ORM\Table(name="role")
 * @ORM\Entity(repositoryClass="Repository\RoleRepository")
 */
class Role
{
    /**
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private ?int $id = null;

    /**
     * @ORM\Column(name="code", type="string", nullable=false, unique=true)
     */
    private ?string $code = null;

    /**
     * @ORM\Column(name="name", type="string", nullable=false)
     */
    private ?string $name = null;

    /**
     * @ORM\Column(name="deletable", type="boolean", nullable=false)
     */
    private bool $deletable = true;

    /**
     * @var Collection|Privilege[]
     *
     * @ORM\ManyToMany(targetEntity="Entity\Privilege", inversedBy="roles")
     */
    private \Doctrine\Common\Collections\Collection|array $privileges;

    /**
     * @var Collection|User[]
     *
     * @ORM\ManyToMany(targetEntity="Entity\User", mappedBy="roles")
     */
    private \Doctrine\Common\Collections\Collection|array $users;

    public function __construct()
    {
        $this->privileges = new ArrayCollection();
        $this->users = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function isDeletable(): bool
    {
        return $this->deletable;
    }

    public function setDeletable(bool $deletable): void
    {
        $this->deletable = $deletable;
    }

    /**
     * @return Collection|Privilege[]
     */
    public function getPrivileges(): \Doctrine\Common\Collections\Collection|array
    {
        return $this->privileges;
    }

    /**
     * @return Collection|User[]
     */
    public function getUsers(): \Doctrine\Common\Collections\Collection|array
    {
        return $this->users;
    }

    /**
     * @param Collection|User[] $users
     */
    public function setUsers(\Doctrine\Common\Collections\Collection|array $users): void
    {
        $this->users = $users;
    }
}