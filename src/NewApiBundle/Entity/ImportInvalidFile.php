<?php declare(strict_types=1);

namespace NewApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class ImportInvalidFile extends AbstractEntity
{
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
     * @var int
     *
     * @ORM\Column(name="invalid_queue_count", type="integer", nullable=false)
     */
    private $invalidQueueCount = 0;

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
