<?php declare(strict_types=1);

namespace NewApiBundle\Component\Import\Message;
use NewApiBundle\Component\Import\IdentityChecker;
use NewApiBundle\Component\Import\IntegrityChecker;
use NewApiBundle\Component\Import\SimilarityChecker;
use NewApiBundle\Enum\ImportQueueState;
use NewApiBundle\Enum\ImportState;
use NewApiBundle\Repository\ImportQueueRepository;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class ItemBatchHandler implements MessageHandlerInterface
{
    /** @var ImportQueueRepository */
    private $queueRepository;
    /** @var IntegrityChecker */
    private $integrityChecker;
    /** @var IdentityChecker */
    private $identityChecker;
    /** @var SimilarityChecker */
    private $similarityChecker;

    /**
     * @param ImportQueueRepository $queueRepository
     * @param IntegrityChecker      $integrityChecker
     * @param IdentityChecker       $identityChecker
     * @param SimilarityChecker     $similarityChecker
     */
    public function __construct(
        ImportQueueRepository $queueRepository,
        IntegrityChecker      $integrityChecker,
        IdentityChecker       $identityChecker,
        SimilarityChecker     $similarityChecker
    ) {
        $this->queueRepository = $queueRepository;
        $this->integrityChecker = $integrityChecker;
        $this->identityChecker = $identityChecker;
        $this->similarityChecker = $similarityChecker;
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
                    $this->similarityChecker->checkOne($item);
                }
                break;
        }
    }
}
