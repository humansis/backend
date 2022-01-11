<?php declare(strict_types=1);

namespace NewApiBundle\Utils\Concurrency;

class ConcurrencyProcessor
{
    /** @var int */
    private $batchSize = 10;
    /** @var callable */
    private $countAllCallback;
    /** @var callable */
    private $lockBatchCallback;
    /** @var callable */
    private $batchItemsCallback;

    /**
     * @param int $batchSize
     *
     * @return ConcurrencyProcessor
     */
    public function setBatchSize(int $batchSize): ConcurrencyProcessor
    {
        $this->batchSize = $batchSize;

        return $this;
    }

    /**
     * @param callable $countAllCallback
     *
     * @return ConcurrencyProcessor
     */
    public function setCountAllCallback(callable $countAllCallback): ConcurrencyProcessor
    {
        $this->countAllCallback = $countAllCallback;

        return $this;
    }

    /**
     * @param callable $lockBatchCallback
     *
     * @return ConcurrencyProcessor
     */
    public function setLockBatchCallback(callable $lockBatchCallback): ConcurrencyProcessor
    {
        $this->lockBatchCallback = $lockBatchCallback;

        return $this;
    }

    /**
     * @param callable $batchItemsCallback
     *
     * @return ConcurrencyProcessor
     */
    public function setBatchItemsCallback(callable $batchItemsCallback): ConcurrencyProcessor
    {
        $this->batchItemsCallback = $batchItemsCallback;

        return $this;
    }

    /**
     * @param callable $processItemCallback
     *
     * @return ConcurrencyProcessor
     */
    public function processItems(callable $processItemCallback): void
    {
        $allItemCountCallback = $this->countAllCallback;
        $lockItemsCallback = $this->lockBatchCallback;
        $getBatchCallback = $this->batchItemsCallback;
        $itemCount = $allItemCountCallback();

        foreach (range(0, $itemCount+$this->batchSize, $this->batchSize) as $batchStart) {
            $runCode = uniqid();
            $lockItemsCallback($runCode, $this->batchSize);

            $itemsToProceed = $getBatchCallback($runCode, $this->batchSize);
            foreach ($itemsToProceed as $item) {
                try {
                    $processItemCallback($item);
                } finally {
                    $item->unlock();
                }
            }
        }
    }
}
