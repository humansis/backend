<?php

declare(strict_types=1);

namespace Component\Import;

use BadMethodCallException;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Entity\ImportHouseholdDuplicity;
use Entity\ImportQueue;
use Enum\ImportDuplicityState;
use Enum\ImportQueueState;
use Enum\ImportState;
use Workflow\ImportTransitions;
use Psr\Log\LoggerInterface;
use Symfony\Component\Workflow\WorkflowInterface;
use Entity\User;

class DuplicityResolver
{
    use ImportQueueLoggerTrait;

    public function __construct(
        private readonly EntityManagerInterface $em,
        LoggerInterface $logger,
        private readonly IdentityChecker $identityChecker,
        private readonly SimilarityChecker $similarityChecker,
        private readonly WorkflowInterface $importQueueStateMachine,
        private readonly WorkflowInterface $importStateMachine
    ) {
        $this->logger = $logger;
    }

    public function resolve(ImportQueue $importQueue, ?int $acceptedDuplicityId, string $status, User $user)
    {
        $import = $importQueue->getImport();
        if (
            !in_array($import->getState(), [
                ImportState::IDENTITY_CHECKING,
                ImportState::IDENTITY_CHECK_CORRECT,
                ImportState::IDENTITY_CHECK_FAILED,
                ImportState::SIMILARITY_CHECKING,
                ImportState::SIMILARITY_CHECK_CORRECT,
                ImportState::SIMILARITY_CHECK_FAILED,
            ])
        ) {
            throw new BadMethodCallException(
                'Unable to execute duplicity resolver. Import is not ready to duplicity resolve.'
            );
        }

        /** @var ImportHouseholdDuplicity[] $duplicities */
        $duplicities = $this->em->getRepository(ImportHouseholdDuplicity::class)->findBy([
            'ours' => $importQueue,
        ]);

        $updates = [];
        $links = [];
        $uniques = [];
        foreach ($duplicities as $duplicity) {
            if ($duplicity->getTheirs()->getId() === $acceptedDuplicityId) {
                switch ($status) {
                    case ImportQueueState::TO_UPDATE:
                        $duplicity->setState(ImportDuplicityState::DUPLICITY_KEEP_OURS);
                        $updates[] = '#' . $duplicity->getId();
                        break;
                    case ImportQueueState::TO_LINK:
                        $duplicity->setState(ImportDuplicityState::DUPLICITY_KEEP_THEIRS);
                        $links[] = '#' . $duplicity->getId();
                        break;
                }
            } else {
                $duplicity->setState(ImportDuplicityState::NO_DUPLICITY);
                $uniques[] = '#' . $duplicity->getId();
            }

            $duplicity->setDecideBy($user);
            $duplicity->setDecideAt(new DateTime());
        }
        $this->importQueueStateMachine->apply($importQueue, $status);
        if (!empty($updates)) {
            $this->logQueueInfo(
                $importQueue,
                "Duplicity suspect(s) [" . implode(', ', $updates) . "] was resolved as more current duplicity"
            );
        } else {
            $this->logQueueDebug(
                $importQueue,
                "[Queue #{$importQueue->getId()}] Nothing was resolved as more current duplicity"
            );
        }
        if (!empty($links)) {
            $this->logQueueInfo(
                $importQueue,
                "Duplicity suspect(s) [" . implode(', ', $updates) . "] was resolved as older duplicity"
            );
        } else {
            $this->logQueueDebug($importQueue, "Nothing was resolved as older duplicity");
        }
        if (!empty($uniques)) {
            $this->logQueueInfo(
                $importQueue,
                "Duplicity suspect(s) [" . implode(', ', $updates) . "] was resolved as mistake and will be inserted"
            );
        } else {
            $this->logQueueDebug($importQueue, "Nothing was resolved as mistake");
        }
    }
}
