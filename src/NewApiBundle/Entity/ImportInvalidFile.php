<?php
declare(strict_types=1);

namespace NewApiBundle\Entity;

use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class ImportInvalidFile
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
     * @var Import
     *
     * @ORM\ManyToOne(targetEntity="NewApiBundle\Entity\Import", inversedBy="importInvalidFiles")
     */
    private $import;

    /**
     * @var DateTimeInterface
     *
     * @ORM\Column(name="created_at", type="datetimetz", nullable=false)
     */
    private $createdAt;

    /**
     * @var int
     *
     * @ORM\Column(name="invalid_queue_count", type="integer", nullable=false)
     */
    private $invalidQueueCount = 0;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
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
    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * @param string $filename
     */
    public function setFilename(string $filename): void
    {
        $this->filename = $filename;
    }

    /**
     * @return Import
     */
    public function getImport(): Import
    {
        return $this->import;
    }

    /**
     * @param Import $import
     */
    public function setImport(Import $import): void
    {
        $this->import = $import;
    }

    /**
     * @return DateTimeInterface
     */
    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    /**
     * @param DateTimeInterface $createdAt
     */
    public function setCreatedAt(DateTimeInterface $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return int
     */
    public function getInvalidQueueCount(): int
    {
        return $this->invalidQueueCount;
    }

    /**
     * @param int $invalidQueueCount
     */
    public function setInvalidQueueCount(int $invalidQueueCount): void
    {
        $this->invalidQueueCount = $invalidQueueCount;
    }

}
