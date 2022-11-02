<?php

declare(strict_types=1);

namespace Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="privilege")
 * @ORM\Entity(repositoryClass="Repository\PrivilegeRepository")
 */
class Privilege
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
     * @var Collection|Role[]
     *
     * @ORM\ManyToMany(targetEntity="Entity\Role", mappedBy="privileges")
     */
    private \Doctrine\Common\Collections\Collection|array $roles;

    public function __construct()
    {
        $this->roles = new ArrayCollection();
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

    /**
     * @return Collection|Role[]
     */
    public function getRoles(): \Doctrine\Common\Collections\Collection|array
    {
        return $this->roles;
    }
}
