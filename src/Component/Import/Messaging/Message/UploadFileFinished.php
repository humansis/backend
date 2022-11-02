<?php

declare(strict_types=1);

namespace Component\Import\Messaging\Message;

use Symfony\Component\Serializer\Annotation\SerializedName;

class UploadFileFinished
{
    public function __construct(private readonly int $importFileId)
    {
    }

    /**
     * @return int
     */
    public function getImportFileId(): ?int
    {
        return $this->importFileId;
    }
}
