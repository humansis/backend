<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Import;

use NewApiBundle\Entity\Import;
use NewApiBundle\Entity\ImportQueue;
use Psr\Log\LoggerInterface;
use Symfony\Component\Workflow\WorkflowInterface;

trait ImportQueueLoggerTrait
{
    /** @var LoggerInterface */
    protected $logger;

    protected function logQueueInfo(ImportQueue $importQueue, string $message): void
    {
        $import = $importQueue->getImport();
        $this->logger->info("[Import#{$import->getId()}][Queue#{$importQueue->getId()}] $message");
    }

    protected function logQueueDebug(ImportQueue $importQueue, string $message): void
    {
        $import = $importQueue->getImport();
        $this->logger->debug("[Import#{$import->getId()}][Queue#{$importQueue->getId()}] $message");
    }

    protected function logQueueWarning(ImportQueue $importQueue, string $message): void
    {
        $import = $importQueue->getImport();
        $this->logger->warning("[Import#{$import->getId()}][Queue#{$importQueue->getId()}] $message");
    }

    protected function logQueueError(ImportQueue $importQueue, string $message): void
    {
        $import = $importQueue->getImport();
        $this->logger->error("[Import#{$import->getId()}][Queue#{$importQueue->getId()}] $message");
    }

    protected function logQueueTransitionConstraints(WorkflowInterface $workflow, ImportQueue $importQueue, string $transition): void
    {
        foreach ($workflow->buildTransitionBlockerList($importQueue, $transition) as $block) {
            $this->logQueueDebug($importQueue, " can't go to '$transition' because ".$block->getMessage());
        }
    }
}
