<?php

declare(strict_types=1);

namespace Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Entity\Helper\CreatedAt;
use Entity\Helper\CreatedBy;
use Entity\Helper\StandardizedPrimaryKey;
use Entity\User;
use Stringable;

#[ORM\Entity(repositoryClass: 'Repository\ImportFileRepository')]
#[ORM\HasLifecycleCallbacks]
class ImportFile implements Stringable
{
    use StandardizedPrimaryKey;
    use CreatedAt;
    use CreatedBy;

    #[ORM\Column(name: 'filename', type: 'string', nullable: false)]
    private string $filename;

    #[ORM\Column(name: 'is_loaded', type: 'boolean')]
    private bool $isLoaded;

    #[ORM\Column(name: 'saved_as_filename', type: 'string', nullable: true)]
    private ?string $savedAsFilename = null;

    #[ORM\ManyToOne(targetEntity: 'Entity\Import', inversedBy: 'importFiles')]
    private Import $import;

    /**
     * @var ImportQueue[]|Collection
     */
    #[ORM\OneToMany(mappedBy: 'file', targetEntity: 'Entity\ImportQueue', cascade: ['remove'])]
    private array | Collection $importQueues;

    #[ORM\Column(name: 'expected_valid_columns', type: 'simple_array', nullable: true)]
    private ?array $expectedValidColumns = null;

    #[ORM\Column(name: 'expected_missing_columns', type: 'simple_array', nullable: true)]
    private ?array $expectedMissingColumns = null;

    #[ORM\Column(name: 'unexpected_columns', type: 'simple_array', nullable: true)]
    private ?array $unexpectedColumns = null;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'structure_violations', type: 'json', nullable: true)]
    private $structureViolations;

    public function __construct(
        string $filename,
        Import $import,
        User $user
    ) {
        $this->filename = $filename;
        $this->import = $import;
        $this->createdBy = $user;
        $this->isLoaded = false;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function getImport(): Import
    {
        return $this->import;
    }

    public function getUser(): User
    {
        return $this->createdBy;
    }

    public function __toString(): string
    {
        return (string) "ImportFile#{$this->getId()} ({$this->getFilename()})";
    }

    /**
     * @return Collection|ImportQueue[]
     */
    public function getImportQueues(): Collection |array
    {
        return $this->importQueues;
    }

    /**
     * @param Collection|ImportQueue[] $importQueues
     */
    public function setImportQueues(Collection |array $importQueues): void
    {
        $this->importQueues = $importQueues;
    }

    public function isLoaded(): bool
    {
        return $this->isLoaded;
    }

    public function setIsLoaded(bool $isLoaded): void
    {
        $this->isLoaded = $isLoaded;
    }

    public function getSavedAsFilename(): ?string
    {
        return $this->savedAsFilename;
    }

    public function setSavedAsFilename(?string $savedAsFilename): void
    {
        $this->savedAsFilename = $savedAsFilename;
    }

    public function getExpectedValidColumns(): ?array
    {
        return $this->expectedValidColumns;
    }

    public function setExpectedValidColumns(?array $expectedValidColumns): void
    {
        $this->expectedValidColumns = $expectedValidColumns;
    }

    public function getExpectedMissingColumns(): ?array
    {
        return $this->expectedMissingColumns;
    }

    public function setExpectedMissingColumns(?array $expectedMissingColumns): void
    {
        $this->expectedMissingColumns = $expectedMissingColumns;
    }

    public function getUnexpectedColumns(): ?array
    {
        return $this->unexpectedColumns;
    }

    public function setUnexpectedColumns(?array $unexpectedColumns): void
    {
        $this->unexpectedColumns = $unexpectedColumns;
    }

    /**
     * @return string|null
     */
    public function getStructureViolations(): ?array
    {
        return $this->structureViolations;
    }

    public function setStructureViolations(?array $structureViolations): void
    {
        $this->structureViolations = $structureViolations;
    }
}
