<?php declare(strict_types=1);

namespace Component\Import\Messaging\Handler;
use Component\Import\ImportLoggerTrait;
use Component\Import\ImportQueueLoggerTrait;
use Component\Import\Messaging\Message\ImportCheck;
use Component\Import\Messaging\Message\UploadFileFinished;
use Component\Import\UploadImportService;
use Repository\ImportFileRepository;
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
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
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
