<?php declare(strict_types=1);

namespace NewApiBundle\Component\Import\Message;
use NewApiBundle\Component\Import\Finishing;
use NewApiBundle\Component\Import\Identity;
use NewApiBundle\Component\Import\ImportQueueLoggerTrait;
use NewApiBundle\Component\Import\Integrity;
use NewApiBundle\Enum\ImportQueueState;
use NewApiBundle\Enum\ImportState;
use NewApiBundle\Repository\ImportQueueRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class ItemBatchHandler implements MessageHandlerInterface
{
    use ImportQueueLoggerTrait;

    /** @var ImportQueueRepository */
    private $queueRepository;
    /** @var Integrity\ItemCheckerService */
    private $integrityChecker;
    /** @var Identity\ItemCheckerService */
    private $identityChecker;
    /** @var Identity\ItemSimilarityCheckerService */
    private $similarityChecker;
    /** @var Finishing\ItemFinishService */
    private $finisher;

    /**
     * @param LoggerInterface                       $importLogger
     * @param ImportQueueRepository                 $queueRepository
     * @param Integrity\ItemCheckerService          $integrityChecker
     * @param Identity\ItemCheckerService           $identityChecker
     * @param Identity\ItemSimilarityCheckerService $similarityChecker
     * @param Finishing\ItemFinishService           $finisher
     */
    public function __construct(
        LoggerInterface                       $importLogger,
        ImportQueueRepository                 $queueRepository,
        Integrity\ItemCheckerService          $integrityChecker,
        Identity\ItemCheckerService           $identityChecker,
        Identity\ItemSimilarityCheckerService $similarityChecker,
        Finishing\ItemFinishService           $finisher
    ) {
        $this->logger = $importLogger;
        $this->queueRepository = $queueRepository;
        $this->integrityChecker = $integrityChecker;
        $this->identityChecker = $identityChecker;
        $this->similarityChecker = $similarityChecker;
        $this->finisher = $finisher;
    }

    public function __invoke(ItemBatch $batch): void
    {
        switch ($batch->getCheckType()) {
            case ImportState::INTEGRITY_CHECKING:
                $items = $this->queueRepository->findBy([
                    'id' => $batch->getQueueItemIds(),
                    'state' => ImportQueueState::NEW,
                ]);
                foreach ($items as $item) {
                    $this->logQueueInfo($item, "Integrity check");
                    $this->integrityChecker->checkOne($item);
                }
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
                    $this->identityChecker->checkBatch($items[0]->getImport(), $items);
                }
                break;
            case ImportState::SIMILARITY_CHECKING:
                $items = $this->queueRepository->findBy([
                    'id' => $batch->getQueueItemIds(),
                    'state' => ImportQueueState::UNIQUE_CANDIDATE,
                ]);
                foreach ($items as $item) {
                    $this->logQueueInfo($item, "Similarity check");
                    $this->similarityChecker->checkOne($item);
                }
                break;
            case ImportState::IMPORTING:
                $items = $this->queueRepository->findBy([
                    'id' => $batch->getQueueItemIds(),
                    'state' => ImportQueueState::TO_CREATE,
                ]);
                foreach ($items as $item) {
                    $this->logQueueInfo($item, "Finish by creation");
                    $this->finisher->finishCreationQueue($item, $item->getImport());
                }
                $items = $this->queueRepository->findBy([
                    'id' => $batch->getQueueItemIds(),
                    'state' => ImportQueueState::TO_UPDATE,
                ]);
                foreach ($items as $item) {
                    $this->logQueueInfo($item, "Finish by update");
                    $this->finisher->finishUpdateQueue($item, $item->getImport());
                }
                $items = $this->queueRepository->findBy([
                    'id' => $batch->getQueueItemIds(),
                    'state' => ImportQueueState::TO_LINK,
                ]);
                foreach ($items as $item) {
                    $this->logQueueInfo($item, "Finish by link");
                    $this->finisher->finishLinkQueue($item, $item->getImport());
                }
                $items = $this->queueRepository->findBy([
                    'id' => $batch->getQueueItemIds(),
                    'state' => ImportQueueState::TO_IGNORE,
                ]);
                foreach ($items as $item) {
                    $this->logQueueInfo($item, "Finish by ignore");
                    $this->finisher->finishIgnoreQueue($item, $item->getImport());
                }
                break;
        }
    }
}
