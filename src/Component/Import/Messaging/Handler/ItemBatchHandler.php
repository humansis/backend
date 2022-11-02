<?php

declare(strict_types=1);

namespace Component\Import\Messaging\Handler;

use Component\Import\IdentityChecker;
use Component\Import\ImportFinisher;
use Component\Import\ImportQueueLoggerTrait;
use Component\Import\IntegrityChecker;
use Component\Import\Messaging\Message\ItemBatch;
use Component\Import\SimilarityChecker;
use Entity\Import;
use Entity\ImportQueue;
use Enum\ImportQueueState;
use Enum\ImportState;
use Repository\ImportQueueRepository;
use Repository\ImportRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class ItemBatchHandler implements MessageHandlerInterface
{
    use ImportQueueLoggerTrait;

    public function __construct(
        LoggerInterface $importLogger,
        private readonly ImportQueueRepository $queueRepository,
        private readonly ImportRepository $importRepository,
        private readonly IntegrityChecker $integrityChecker,
        private readonly IdentityChecker $identityChecker,
        private readonly SimilarityChecker $similarityChecker,
        private readonly ImportFinisher $finisher
    ) {
        $this->logger = $importLogger;
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
                $this->foreach(
                    $batch,
                    ImportQueueState::UNIQUE_CANDIDATE,
                    function (Import $import, ImportQueue $item) {
                        $this->logQueueInfo($item, "Similarity check");
                        $this->similarityChecker->checkOne($item);
                        $this->queueRepository->save($item);
                    }
                );
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
