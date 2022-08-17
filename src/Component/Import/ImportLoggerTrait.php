<?php
declare(strict_types=1);

namespace Component\Import;

use Entity\Import;
use Psr\Log\LoggerInterface;
use Symfony\Component\Workflow\WorkflowInterface;

trait ImportLoggerTrait
{
    /** @var LoggerInterface */
    protected $logger;

    protected function logImportInfo(Import $import, string $message): void
    {
        $this->logger->info("[Import#{$import->getId()}] ({$import->getTitle()}|{$import->getState()}) $message");
    }

    protected function logImportDebug(Import $import, string $message): void
    {
        $this->logger->debug("[Import#{$import->getId()}] ({$import->getTitle()}|{$import->getState()}) $message");
    }

    protected function logImportWarning(Import $import, string $message): void
    {
        $this->logger->warning("[Import#{$import->getId()}] ({$import->getTitle()}|{$import->getState()}) $message");
    }

    protected function logImportError(Import $import, string $message): void
    {
        $this->logger->error("[Import#{$import->getId()}] ({$import->getTitle()}|{$import->getState()}) $message");
    }

    protected function logImportTransitionConstraints(WorkflowInterface $workflow, Import $import, string $transition): void
    {
        foreach ($workflow->buildTransitionBlockerList($import, $transition) as $block) {
            $this->logImportDebug($import, " can't go to '$transition' because ".$block->getMessage());
        }
    }
}
