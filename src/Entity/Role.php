<?php

declare(strict_types=1);

namespace Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Entity\Helper\StandardizedPrimaryKey;

#[ORM\Table(name: 'role')]
#[ORM\Entity(repositoryClass: 'Repository\RoleRepository')]
class Role
{
    use StandardizedPrimaryKey;

    #[ORM\Column(name: 'code', type: 'string', unique: true, nullable: false)]
    private string|null $code = null;

    #[ORM\Column(name: 'name', type: 'string', nullable: false)]
    private string|null $name = null;

    #[ORM\Column(name: 'deletable', type: 'boolean', nullable: false)]
    private bool $deletable = true;

    /**
     * @var Collection|Privilege[]
     */
    #[ORM\ManyToMany(targetEntity: 'Entity\Privilege', inversedBy: 'roles')]
    private Collection |array $privileges;

    /**
     * @var Collection|User[]
     */
    #[ORM\ManyToMany(targetEntity: 'Entity\User', mappedBy: 'roles')]
    private Collection |array $users;

    public function __construct()
    {
        $this->privileges = new ArrayCollection();
        $this->users = new ArrayCollection();
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
    public function getPrivileges(): Collection |array
    {
        return $this->privileges;
    }

    /**
     * @return Collection|User[]
     */
    public function getUsers(): Collection |array
    {
        return $this->users;
    }

    /**
     * @param Collection|User[] $users
     */
    public function setUsers(Collection |array $users): void
    {
        $this->users = $users;
    }
}
