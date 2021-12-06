<?php
declare(strict_types=1);

namespace Tests\NewApiBundle\Component\Import\Helper;

use NewApiBundle\Component\Import\Entity\Import;
use NewApiBundle\Component\Import\Entity\Queue;

trait ChecksTrait
{
    private function assertQueueCount(int $expectedCount, Import $import, ?array $filterQueueStates = null): void
    {
        if ($filterQueueStates === null) {
            $queueCount = $this->entityManager->getRepository(Queue::class)->count(['import' => $import]);
            $this->assertEquals($expectedCount, $queueCount, 'There should be other amount of queue items');
        } else {
            $queueCount = $this->entityManager->getRepository(Queue::class)->count([
                'import' => $import,
                'state' => $filterQueueStates
            ]);
            $this->assertEquals($expectedCount, $queueCount);
        }
    }
}
