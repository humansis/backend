<?php declare(strict_types=1);

namespace NewApiBundle\Component\Import\Message;

use NewApiBundle\Entity\ImportQueue;
use NewApiBundle\Enum\ImportState;

class ItemBatch implements \JsonSerializable
{
    /** @var string */
    private $checkType;
    /** @var int[] */
    private $queueItemIds = [];

    /**
     * @param string|null $checkType
     * @param int[]       $queueItemIds
     */
    private function __construct(?string $checkType = null, ?array $queueItemIds=null)
    {
        $this->queueItemIds = $queueItemIds;
        $this->checkType = $checkType;
    }

    /**
     * @param ImportQueue $item
     *
     * @return static
     */
    public static function checkSingleItemIntegrity(ImportQueue $item): self
    {
        return new self(ImportState::INTEGRITY_CHECKING, [$item->getId()]);
    }

    /**
     * @param ImportQueue $item
     *
     * @return static
     */
    public static function checkSingleItemIdentity(ImportQueue $item): self
    {
        return new self(ImportState::IDENTITY_CHECKING, [$item->getId()]);
    }

    /**
     * @param ImportQueue $item
     *
     * @return static
     */
    public static function checkSingleItemSimilarity(ImportQueue $item): self
    {
        return new self(ImportState::SIMILARITY_CHECKING, [$item->getId()]);
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


    public function jsonSerialize()
    {
        return [
            'ids' => $this->getQueueItemIds(),
        ];
    }
}
