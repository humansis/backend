<?php
declare(strict_types=1);

namespace NewApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use UserBundle\Entity\User;

/**
 * @ORM\Entity()
 */
class ImportFile
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
     * @ORM\Column(name="filename", type="string", nullable=false)
     */
    private $filename;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_loaded", type="boolean")
     */
    private $isLoaded;

    /**
     * @var Import
     *
     * @ORM\ManyToOne(targetEntity="NewApiBundle\Entity\Import")
     */
    private $import;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="UserBundle\Entity\User")
     */
    private $user;

    /**
     * @var string
     *
     * @ORM\Column(name="created_at", type="datetimetz", nullable=false)
     */
    private $createdAt;

    public function __construct(string $filename, Import $import, User $user)
    {
        $this->filename = $filename;
        $this->import = $import;
        $this->user = $user;
        $this->createdAt = new \DateTime('now');
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * @return Import
     */
    public function getImport(): Import
    {
        return $this->import;
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    /**
     * @return bool
     */
    public function isLoaded(): bool
    {
        return $this->isLoaded;
    }

    /**
     * @param bool $isLoaded
     */
    public function setIsLoaded(bool $isLoaded): void
    {
        $this->isLoaded = $isLoaded;
    }
}
