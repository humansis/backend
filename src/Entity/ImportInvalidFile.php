<?php

declare(strict_types=1);

namespace Entity;

use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Entity\Helper\CreatedAt;
use Entity\Helper\StandardizedPrimaryKey;

/**
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks()
 */
class ImportInvalidFile
{
    use StandardizedPrimaryKey;
    use CreatedAt;

    /**
     * @ORM\Column(name="filename", type="string", nullable=false)
     */
    private ?string $filename = null;

    /**
     * @ORM\ManyToOne(targetEntity="Entity\Import", inversedBy="importInvalidFiles")
     */
    private ?\Entity\Import $import = null;

    /**
     * @ORM\Column(name="invalid_queue_count", type="integer", nullable=false)
     */
    private int $invalidQueueCount = 0;

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): void
    {
        $this->filename = $filename;
    }

    public function getImport(): Import
    {
        return $this->import;
    }

    public function setImport(Import $import): void
    {
        $this->import = $import;
    }

    public function getInvalidQueueCount(): int
    {
        return $this->invalidQueueCount;
    }

    public function setInvalidQueueCount(int $invalidQueueCount): void
    {
        $this->invalidQueueCount = $invalidQueueCount;
    }
}
