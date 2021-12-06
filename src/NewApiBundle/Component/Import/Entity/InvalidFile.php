<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Import\Entity;

use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use NewApiBundle\Entity\Helper\StandardizedPrimaryKey;

/**
 * @ORM\Table(name="import_invalid_file")
 * @ORM\Entity()
 */
class InvalidFile
{
    use StandardizedPrimaryKey;

    /**
     * @var string
     *
     * @ORM\Column(name="filename", type="string", nullable=false)
     */
    private $filename;

    /**
     * @var Import
     *
     * @ORM\ManyToOne(targetEntity="NewApiBundle\Component\Import\Entity\Import", inversedBy="importInvalidFiles")
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
