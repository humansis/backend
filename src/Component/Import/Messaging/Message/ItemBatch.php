<?php

declare(strict_types=1);

namespace Component\Import\Messaging\Message;

use Entity\ImportQueue;
use Enum\ImportState;
use Symfony\Component\Serializer\Annotation\SerializedName;

class ItemBatch
{
    /**
     * @SerializedName("checkType")
     * @var string
     */
    private $checkType;

    /**
     * @SerializedName("importId")
     * @var int
     */
    private $importId;

    /**
     * @SerializedName("queueItemIds")
     * @var array
     */
    private $queueItemIds = [];

    /**
     * @param string|null $checkType
     * @param int[]|null $queueItemIds
     */
    private function __construct(int $importId, ?string $checkType = null, ?array $queueItemIds = null)
    {
        $this->queueItemIds = $queueItemIds;
        $this->checkType = $checkType;
        $this->importId = $importId;
    }

    /**
     * @param ImportQueue $item
     *
     * @return static
     */
    public static function checkSingleItemIntegrity(ImportQueue $item): self
    {
        return new self($item->getImport()->getId(), ImportState::INTEGRITY_CHECKING, [$item->getId()]);
    }

    /**
     * @param ImportQueue $item
     *
     * @return static
     */
    public static function checkSingleItemIdentity(ImportQueue $item): self
    {
        return new self($item->getImport()->getId(), ImportState::IDENTITY_CHECKING, [$item->getId()]);
    }

    public static function finishSingleItem(ImportQueue $item): self
    {
        return new self($item->getImport()->getId(), ImportState::IMPORTING, [$item->getId()]);
    }

    /**
     * @param ImportQueue $item
     *
     * @return static
     */
    public static function checkSingleItemSimilarity(ImportQueue $item): self
    {
        return new self($item->getImport()->getId(), ImportState::SIMILARITY_CHECKING, [$item->getId()]);
    }

    /**
     * @return int
     */
    public function getImportId(): int
    {
        return $this->importId;
    }

    /**
     * @param int $importId
     *
     * @return ItemBatch
     */
    public function setImportId(int $importId): ItemBatch
    {
        $this->importId = $importId;

        return $this;
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
     * @return int[]
     */
    public function getQueueItemIds(): ?array
    {
        return $this->queueItemIds;
    }

    /**
     * @param int[] $queueItemIds
     */
    public function setQueueItemIds(array $queueItemIds): void
    {
        $this->queueItemIds = $queueItemIds;
    }

    /**
     * @param ImportQueue[] $queueItems
     */
    public function setQueueItems(iterable $queueItems): void
    {
        $this->queueItemIds = array_map(function (ImportQueue $queue) {
            return $queue->getId();
        }, $queueItems);
    }
}
