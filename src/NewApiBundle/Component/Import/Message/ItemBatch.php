<?php declare(strict_types=1);

namespace NewApiBundle\Component\Import\Message;

use NewApiBundle\Entity\ImportQueue;

class ItemBatch implements \JsonSerializable
{
    /** @var string */
    private $checkType;
    /** @var int[] */
    private $queueItemIds = [];

    /**
     * @param int[]  $queueItemIds
     * @param string $checkType
     */
    public function __construct(?string $checkType = null, ?array $queueItemIds=null)
    {
        $this->queueItemIds = $queueItemIds;
        $this->checkType = $checkType;
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
