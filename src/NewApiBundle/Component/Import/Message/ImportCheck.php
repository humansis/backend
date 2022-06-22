<?php declare(strict_types=1);

namespace NewApiBundle\Component\Import\Message;

use NewApiBundle\Entity\Import;
use NewApiBundle\Entity\ImportQueue;
use NewApiBundle\Enum\ImportState;

class ImportCheck implements \JsonSerializable
{
    /** @var string */
    private $checkType;
    /** @var int */
    private $importId;

    /**
     * @param string|null $checkType
     * @param int|null    $importId
     */
    private function __construct(?string $checkType = null, ?int $importId=null)
    {
        $this->importId = $importId;
        $this->checkType = $checkType;
    }

    /**
     * @param Import $import
     *
     * @return static
     */
    public static function checkIntegrityComplete(Import $import): self
    {
        return new self(ImportState::INTEGRITY_CHECKING, $import->getId());
    }

    /**
     * @param Import $import
     *
     * @return static
     */
    public static function checkIdentityComplete(Import $import): self
    {
        return new self(ImportState::IDENTITY_CHECKING, $import->getId());
    }

    /**
     * @param Import $import
     *
     * @return static
     */
    public static function checkSimilarityComplete(Import $import): self
    {
        return new self(ImportState::SIMILARITY_CHECKING, $import->getId());
    }

    /**
     * @return string
     */
    public function getCheckType(): ?string
    {
        return $this->checkType;
    }

    /**
     * @param string $checkType
     */
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

    /**
     * @param int $importId
     */
    public function setImportId(int $importId): void
    {
        $this->importId = $importId;
    }


    public function jsonSerialize()
    {
        return [
            'type' => $this->getCheckType(),
            'id' => $this->getImportId(),
        ];
    }
}
