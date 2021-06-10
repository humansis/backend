<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Import;

use NewApiBundle\Entity\Import;
use Psr\Log\LoggerInterface;

trait ImportLoggerTrait
{
    /** @var LoggerInterface */
    protected $logger;

    protected function logImportInfo(Import $import, string $message): void
    {
        $this->logger->info("[Import #{$import->getId()}] ({$import->getTitle()}) $message");
    }

    protected function logImportDebug(Import $import, string $message): void
    {
        $this->logger->debug("[Import #{$import->getId()}] ({$import->getTitle()}) $message");
    }

    protected function logImportWarning(Import $import, string $message): void
    {
        $this->logger->warning("[Import #{$import->getId()}] ({$import->getTitle()}) $message");
    }
}
