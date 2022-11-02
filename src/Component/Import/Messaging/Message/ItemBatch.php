<?php

declare(strict_types=1);

namespace Component\Import\Messaging\Message;

use Entity\ImportQueue;
use Enum\ImportState;
use Symfony\Component\Serializer\Annotation\SerializedName;

class ItemBatch
{
    /**
     * @param int[]|null $queueItemIds
     */
    private function __construct(#[SerializedName('importId')]
    private int $importId, private ?string $checkType = null, private ?array $queueItemIds = null)
    {
    }

    /**
     * @return static
     */
    public static function checkSingleItemIntegrity(ImportQueue $item): self
    {
        return new self($item->getImport()->getId(), ImportState::INTEGRITY_CHECKING, [$item->getId()]);
    }

    /**
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
     * @return static
     */
    public static function checkSingleItemSimilarity(ImportQueue $item): self
    {
        return new self($item->getImport()->getId(), ImportState::SIMILARITY_CHECKING, [$item->getId()]);
    }

    public function getImportId(): int
    {
        return $this->importId;
    }

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
        $this->queueItemIds = array_map(fn(ImportQueue $queue) => $queue->getId(), $queueItems);
    }
}
