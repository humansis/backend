<?php declare(strict_types=1);

namespace NewApiBundle\Component\Import\Messaging\Message;

use Symfony\Component\Serializer\Annotation\SerializedName;

class UploadFile
{
    /**
     *
     * @SerializedName("importFileId")
     * @var int
     */
    private $importFileId;

    /**
     * @param int $importFileId
     */
    public function __construct(int $importFileId)
    {
        $this->importFileId = $importFileId;
    }

    /**
     * @return int
     */
    public function getImportFileId(): ?int
    {
        return $this->importFileId;
    }



}
