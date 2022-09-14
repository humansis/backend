<?php
declare(strict_types=1);

namespace NewApiBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="privilege")
 * @ORM\Entity(repositoryClass="NewApiBundle\Repository\PrivilegeRepository")
 */
class Privilege extends AbstractEntity
{
    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", nullable=false, unique=true)
     */
    private $code;

    /**
     * @var Collection|Role[]
     *
     * @ORM\ManyToMany(targetEntity="NewApiBundle\Entity\Role", mappedBy="privileges")
     */
    private $roles;

    public function __construct()
    {
        $this->roles = new ArrayCollection();
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
     * @return Collection|Role[]
     */
    public function getRoles()
    {
        return $this->roles;
    }
}
