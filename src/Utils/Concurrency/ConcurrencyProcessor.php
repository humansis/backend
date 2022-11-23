<?php

declare(strict_types=1);

namespace Utils\Concurrency;

use Exception;

class ConcurrencyProcessor
{
    private int $batchSize = 10;

    /** @var callable */
    private $countAllCallback;

    /** @var callable */
    private $lockBatchCallback;

    /** @var callable */
    private $batchItemsCallback;

    /** @var callable */
    private $batchUnlockCallback;

    /** @var callable */
    private $batchCleanupCallback;

    private ?int $maxResultsToProcess = null;

    public function setBatchSize(int $batchSize): ConcurrencyProcessor
    {
        $this->batchSize = $batchSize;

        return $this;
    }

    public function setCountAllCallback(callable $countAllCallback): ConcurrencyProcessor
    {
        $this->countAllCallback = $countAllCallback;

        return $this;
    }

    public function setLockBatchCallback(callable $lockBatchCallback): ConcurrencyProcessor
    {
        $this->lockBatchCallback = $lockBatchCallback;

        return $this;
    }

    public function setBatchItemsCallback(callable $batchItemsCallback): ConcurrencyProcessor
    {
        $this->batchItemsCallback = $batchItemsCallback;

        return $this;
    }

    public function setUnlockItemsCallback(callable $batchUnlockCallback): ConcurrencyProcessor
    {
        $this->batchUnlockCallback = $batchUnlockCallback;

        return $this;
    }

    public function setBatchCleanupCallback(callable $batchCleanupCallback): ConcurrencyProcessor
    {
        $this->batchCleanupCallback = $batchCleanupCallback;

        return $this;
    }

    public function setMaxResultsToProcess(int $maxResultsToProcess): ConcurrencyProcessor
    {
        $this->maxResultsToProcess = $maxResultsToProcess;

        return $this;
    }

    public function processItems(callable $processItemCallback): void
    {
        $allItemCountCallback = $this->countAllCallback;
        $lockItemsCallback = $this->lockBatchCallback;
        $getBatchCallback = $this->batchItemsCallback;
        $batchUnlockCallback = $this->batchUnlockCallback;
        $batchCleanupCallback = $this->batchCleanupCallback ?? function () {
        };
        $itemCount = $allItemCountCallback();

        if ($itemCount === 0) {
            return;
        }

        $totalItemsToProcess = $itemCount > $this->maxResultsToProcess ? $this->maxResultsToProcess : $itemCount;
        $rounds = ceil($totalItemsToProcess / $this->batchSize);
        for ($i = 0; $i < $rounds; $i++) {
            $runCode = uniqid();
            $lockItemsCallback($runCode, $this->batchSize);
            $itemsToProceed = $getBatchCallback($runCode, $this->batchSize);
            foreach ($itemsToProceed as $item) {
                try {
                    $processItemCallback($item);
                } catch (Exception) {
                    $batchUnlockCallback($runCode);
                    break;
                }
            }
            $batchCleanupCallback();
        }
    }
}
