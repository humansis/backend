<?php

declare(strict_types=1);

namespace Component\Import\Messaging\Message;

use Entity\Import;
use Enum\ImportState;
use Symfony\Component\Serializer\Annotation\SerializedName;

class ImportCheck
{
    private function __construct(private ?string $checkType = null, private ?int $importId = null)
    {
    }

    /**
     * @return static
     */
    public static function checkUploadingComplete(Import $import): self
    {
        return new self(ImportState::UPLOADING, $import->getId());
    }

    /**
     * @return static
     */
    public static function checkIntegrityComplete(Import $import): self
    {
        return new self(ImportState::INTEGRITY_CHECKING, $import->getId());
    }

    /**
     * @return static
     */
    public static function checkIdentityComplete(Import $import): self
    {
        return new self(ImportState::IDENTITY_CHECKING, $import->getId());
    }

    /**
     * @return static
     */
    public static function checkSimilarityComplete(Import $import): self
    {
        return new self(ImportState::SIMILARITY_CHECKING, $import->getId());
    }

    /**
     * @return static
     */
    public static function checkImportingComplete(Import $import): self
    {
        return new self(ImportState::IMPORTING, $import->getId());
    }

    /**
     * @return string
     */
    public function getCheckType(): ?string
    {
        return $this->checkType;
    }

    public function setCheckType(string $checkType): void
    {
        $this->checkType = $checkType;
    }

    /**
     * @return int
     */
    public function getImportId(): ?int
    {
        return $this->importId;
    }

    public function setImportId(int $importId): void
    {
        $this->importId = $importId;
    }
}
