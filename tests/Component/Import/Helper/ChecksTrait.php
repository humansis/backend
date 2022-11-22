<?php

declare(strict_types=1);

namespace Tests\Component\Import\Helper;

use Entity\Import;
use Entity\ImportQueue;

trait ChecksTrait
{
    private function assertQueueCount(int $expectedCount, Import $import, ?array $filterQueueStates = null): void
    {
        if ($filterQueueStates === null) {
            $queueCount = $this->entityManager->getRepository(ImportQueue::class)->count(['import' => $import]);
            $this->assertEquals($expectedCount, $queueCount, 'There should be other amount of queue items');
        } else {
            $queueCount = $this->entityManager->getRepository(ImportQueue::class)->count([
                'import' => $import,
                'state' => $filterQueueStates,
            ]);
            $this->assertEquals($expectedCount, $queueCount);
        }
    }
}
