<?php declare(strict_types=1);

namespace NewApiBundle\Component\Import\Messaging\Handler;
use NewApiBundle\Component\Import\ImportLoggerTrait;
use NewApiBundle\Component\Import\ImportQueueLoggerTrait;
use NewApiBundle\Component\Import\Messaging\Message\ImportCheck;
use NewApiBundle\Component\Import\Messaging\Message\UploadFileFinished;
use NewApiBundle\Component\Import\UploadImportService;
use NewApiBundle\Repository\ImportFileRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class UploadImportHandler implements MessageHandlerInterface
{
    use ImportQueueLoggerTrait;
    use ImportLoggerTrait;

    /** @var ImportFileRepository */
    private $importFileRepository;

    /** @var UploadImportService */
    private $uploadImportService;

    /** @var MessageBusInterface */
    private $messageBus;

    /**
     * @param LoggerInterface      $importLogger
     * @param ImportFileRepository $importFileRepository
     * @param UploadImportService  $uploadImportService
     * @param MessageBusInterface  $messageBus
     */
    public function __construct(
        LoggerInterface            $importLogger,
        ImportFileRepository       $importFileRepository,
        UploadImportService        $uploadImportService,
        MessageBusInterface        $messageBus
    ) {
        $this->logger = $importLogger;
        $this->importFileRepository = $importFileRepository;
        $this->uploadImportService = $uploadImportService;
        $this->messageBus = $messageBus;
    }

    /**
     * @param UploadFileFinished $uploadFile
     *
     */
    public function __invoke(UploadFileFinished $uploadFile): void
    {
        $importFile = $this->importFileRepository->find($uploadFile->getImportFileId());
        if ($importFile !== null) {
            try {
                $this->uploadImportService->load($importFile);
            } catch (\Throwable $ex) {
                $this->logImportWarning($importFile->getImport(), $ex->getMessage());
            } finally {
                $this->messageBus->dispatch(ImportCheck::checkUploadingComplete($importFile->getImport()));
            }
        } else {
            $this->logger->warning("Import file {$uploadFile->getImportFileId()} upload was not finished because import file entity is not in database");
        }
    }
}
