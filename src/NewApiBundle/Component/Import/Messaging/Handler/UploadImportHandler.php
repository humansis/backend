<?php declare(strict_types=1);

namespace NewApiBundle\Component\Import\Messaging\Handler;
use NewApiBundle\Component\Import\ImportQueueLoggerTrait;
use NewApiBundle\Component\Import\Messaging\Message\ImportCheck;
use NewApiBundle\Component\Import\Messaging\Message\UploadFile;
use NewApiBundle\Component\Import\UploadImportService;
use NewApiBundle\Repository\ImportFileRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class UploadImportHandler implements MessageHandlerInterface
{
    use ImportQueueLoggerTrait;

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
     * @param UploadFile $uploadFile
     *
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function __invoke(UploadFile $uploadFile): void
    {
        $importFile = $this->importFileRepository->find($uploadFile->getImportFileId());
        if ($importFile !== null) {
            $this->uploadImportService->load($importFile);
            $this->messageBus->dispatch(ImportCheck::checkUploadingComplete($importFile->getImport()));
        }
    }
}
