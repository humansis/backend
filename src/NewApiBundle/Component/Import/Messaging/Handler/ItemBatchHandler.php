<?php declare(strict_types=1);

namespace NewApiBundle\Component\Import\Messaging\Handler;
use NewApiBundle\Component\Import\IdentityChecker;
use NewApiBundle\Component\Import\ImportFinisher;
use NewApiBundle\Component\Import\ImportQueueLoggerTrait;
use NewApiBundle\Component\Import\IntegrityChecker;
use NewApiBundle\Component\Import\Messaging\Message\ItemBatch;
use NewApiBundle\Component\Import\SimilarityChecker;
use NewApiBundle\Entity\Import;
use NewApiBundle\Entity\ImportQueue;
use NewApiBundle\Enum\ImportQueueState;
use NewApiBundle\Enum\ImportState;
use NewApiBundle\Repository\ImportQueueRepository;
use NewApiBundle\Repository\ImportRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class ItemBatchHandler implements MessageHandlerInterface
{
    use ImportQueueLoggerTrait;

    /** @var ImportQueueRepository */
    private $queueRepository;
    /** @var ImportRepository */
    private $importRepository;
    /** @var IntegrityChecker */
    private $integrityChecker;
    /** @var IdentityChecker */
    private $identityChecker;
    /** @var SimilarityChecker */
    private $similarityChecker;
    /** @var ImportFinisher */
    private $finisher;

    /**
     * @param LoggerInterface       $importLogger
     * @param ImportQueueRepository $queueRepository
     * @param IntegrityChecker      $integrityChecker
     * @param IdentityChecker       $identityChecker
     * @param SimilarityChecker     $similarityChecker
     * @param ImportFinisher        $finisher
     */
    public function __construct(
        LoggerInterface       $importLogger,
        ImportQueueRepository $queueRepository,
        ImportRepository      $importRepository,
        IntegrityChecker      $integrityChecker,
        IdentityChecker       $identityChecker,
        SimilarityChecker     $similarityChecker,
        ImportFinisher        $finisher
    ) {
        $this->logger = $importLogger;
        $this->queueRepository = $queueRepository;
        $this->integrityChecker = $integrityChecker;
        $this->identityChecker = $identityChecker;
        $this->similarityChecker = $similarityChecker;
        $this->finisher = $finisher;
        $this->importRepository = $importRepository;
    }

    public function __invoke(ItemBatch $batch): void
    {
        $import = $this->importRepository->find($batch->getImportId());
        switch ($batch->getCheckType()) {
            case ImportState::INTEGRITY_CHECKING:
                $this->foreach($batch, ImportQueueState::NEW, function (Import $import, ImportQueue $item) {
                    $this->logQueueInfo($item, "Integrity check");
                    $this->integrityChecker->checkOne($item);
                    $this->queueRepository->save($item);
                });
                break;
            case ImportState::IDENTITY_CHECKING:
                $items = $this->queueRepository->findBy([
                    'id' => $batch->getQueueItemIds(),
                    'state' => ImportQueueState::VALID,
                ]);
                $queueByImport = [];
                foreach ($items as $item) {
                    $this->logQueueInfo($item, "Identity check");
                    $queueByImport[$item->getImport()->getId()][] = $item;
                }
                foreach ($queueByImport as $items) {
                    $this->identityChecker->checkBatch($import, $items);
                }
                foreach ($items as $item) {
                    $this->queueRepository->save($item);
                }
                break;
            case ImportState::SIMILARITY_CHECKING:
                $this->foreach($batch, ImportQueueState::UNIQUE_CANDIDATE, function (Import $import, ImportQueue $item) {
                    $this->logQueueInfo($item, "Similarity check");
                    $this->similarityChecker->checkOne($item);
                    $this->queueRepository->save($item);
                });
                break;
            case ImportState::IMPORTING:
                $this->foreach($batch, ImportQueueState::TO_CREATE, function (Import $import, ImportQueue $item) {
                    $this->logQueueInfo($item, "Finish by creation");
                    $this->finisher->finishCreationQueue($item, $item->getImport());
                    $this->queueRepository->save($item);
                });
                $this->foreach($batch, ImportQueueState::TO_UPDATE, function (Import $import, ImportQueue $item) {
                    $this->logQueueInfo($item, "Finish by update");
                    $this->finisher->finishUpdateQueue($item, $item->getImport());
                    $this->queueRepository->save($item);
                });
                $this->foreach($batch, ImportQueueState::TO_LINK, function (Import $import, ImportQueue $item) {
                    $this->logQueueInfo($item, "Finish by link");
                    $this->finisher->finishLinkQueue($item, $item->getImport());
                    $this->queueRepository->save($item);
                });
                $this->foreach($batch, ImportQueueState::TO_IGNORE, function (Import $import, ImportQueue $item) {
                    $this->logQueueInfo($item, "Finish by ignore");
                    $this->finisher->finishIgnoreQueue($item, $item->getImport());
                    $this->queueRepository->save($item);
                });
                break;
        }
    }

    private function foreach(ItemBatch $batch, string $state, callable $itemCallback)
    {
        $items = $this->queueRepository->findBy([
            'id' => $batch->getQueueItemIds(),
            'state' => $state,
        ]);
        foreach ($items as $item) {
            $itemCallback($item->getImport(), $item);
        }
    }
}
