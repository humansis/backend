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
    /** @var int */
    private $maxResultsToProcess;

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
     * @param int $maxResultsToProcess
     *
     * @return ConcurrencyProcessor
     */
    public function setMaxResultsToProcess(int $maxResultsToProcess): ConcurrencyProcessor
    {
        $this->maxResultsToProcess = $maxResultsToProcess;

        return $this;
    }

    /**
     * @param callable $processItemCallback
     *
     */
    public function processItems(callable $processItemCallback, $logger): void
    {
        $allItemCountCallback = $this->countAllCallback;
        $lockItemsCallback = $this->lockBatchCallback;
        $getBatchCallback = $this->batchItemsCallback;
        $itemCount = $allItemCountCallback();

        if ($itemCount === 0) {
            return;
        }


        $totalItemsToProcess = $itemCount > $this->maxResultsToProcess ? $this->maxResultsToProcess : $itemCount;
        $logger->error('Number of records to process: '. $totalItemsToProcess);
        $rounds = ceil($totalItemsToProcess / $this->batchSize);
        $logger->error('Rounds: '. $rounds);
        for ($i = 0; $i < $rounds; $i++) {
            $runCode = uniqid();
            $logger->error('Code '. $runCode);
            $lockItemsCallback($runCode, $this->batchSize);
            $logger->error('Items locked '. $runCode . ' ' . $this->batchSize);
            $itemsToProceed = $getBatchCallback($runCode, $this->batchSize);
            $logger->error('Items to proceed '. count($itemsToProceed));
            foreach ($itemsToProceed as $item) {
                try {
                    $processItemCallback($item);
                } finally {
                    $item->unlock();
                }
            }
            $logger->error('Done '. count($itemsToProceed));
        }
    }
}
