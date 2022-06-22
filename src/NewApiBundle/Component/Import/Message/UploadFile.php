<?php declare(strict_types=1);

namespace NewApiBundle\Component\Import\Message;

use NewApiBundle\Entity\Import;
use NewApiBundle\Entity\ImportFile;
use NewApiBundle\Entity\ImportQueue;
use NewApiBundle\Enum\ImportState;

class UploadFile implements \JsonSerializable
{
    /** @var int */
    private $importFileId;

    /**
     * @param ImportFile $importFile
     */
    public function __construct(ImportFile $importFile)
    {
        $this->importFileId = $importFile->getId();
    }

    /**
     * @return int
     */
    public function getImportFileId(): ?int
    {
        return $this->importFileId;
    }


    public function jsonSerialize()
    {
        return [
            'id' => $this->importFileId,
        ];
    }
}
