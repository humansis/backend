<?php

declare(strict_types=1);

namespace Component\Import\Messaging\Handler;

use Component\Auditor\AuditorService;
use Component\Import\ImportLoggerTrait;
use Component\Import\ImportQueueLoggerTrait;
use Component\Import\Messaging\Message\ImportCheck;
use Component\Import\Messaging\Message\UploadFileFinished;
use Component\Import\UploadImportService;
use Psr\Log\LoggerInterface;
use Repository\ImportFileRepository;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Throwable;

class UploadImportHandler implements MessageHandlerInterface
{
    use ImportQueueLoggerTrait;
    use ImportLoggerTrait;

    public function __construct(
        LoggerInterface $importLogger,
        private readonly ImportFileRepository $importFileRepository,
        private readonly UploadImportService $uploadImportService,
        private readonly MessageBusInterface $messageBus,
        private readonly AuditorService $auditorService
    ) {
        $this->logger = $importLogger;
    }

    public function __invoke(UploadFileFinished $uploadFile): void
    {
        $this->auditorService->disableAuditing();

        $importFile = $this->importFileRepository->find($uploadFile->getImportFileId());
        if ($importFile !== null) {
            try {
                $this->uploadImportService->load($importFile);
            } catch (Throwable $ex) {
                $this->logImportWarning($importFile->getImport(), $ex->getMessage());
            } finally {
                $this->messageBus->dispatch(ImportCheck::checkUploadingComplete($importFile->getImport()));
            }
        } else {
            $this->logger->warning(
                "Import file {$uploadFile->getImportFileId()} upload was not finished because import file entity is not in database"
            );
        }
    }
}
