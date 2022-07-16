<?php
declare(strict_types=1);

namespace NewApiBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use NewApiBundle\Entity\User;

/**
 * @ORM\Table(name="role")
 * @ORM\Entity(repositoryClass="NewApiBundle\Repository\RoleRepository")
 */
class Role
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
     * @ORM\Column(name="code", type="string", nullable=false, unique=true)
     */
    private $code;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", nullable=false)
     */
    private $name;

    /**
     * @var boolean
     *
     * @ORM\Column(name="deletable", type="boolean", nullable=false)
     */
    private $deletable = true;

    /**
     * @var Collection|Privilege[]
     *
     * @ORM\ManyToMany(targetEntity="NewApiBundle\Entity\Privilege", inversedBy="roles")
     */
    private $privileges;

    /**
     * @var Collection|User[]
     *
     * @ORM\ManyToMany(targetEntity="NewApiBundle\Entity\User", mappedBy="roles")
     */
    private $users;

    public function __construct()
    {
        $this->privileges = new ArrayCollection();
        $this->users = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @param string $code
     */
    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return bool
     */
    public function isDeletable(): bool
    {
        return $this->deletable;
    }

    /**
     * @param bool $deletable
     */
    public function setDeletable(bool $deletable): void
    {
        $this->deletable = $deletable;
    }

    /**
     * @return Collection|Privilege[]
     */
    public function getPrivileges()
    {
        return $this->privileges;
    }

    /**
     * @return Collection|User[]
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * @param Collection|User[] $users
     */
    public function setUsers($users): void
    {
        $this->users = $users;
    }
}
