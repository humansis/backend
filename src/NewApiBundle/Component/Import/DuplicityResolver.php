<?php declare(strict_types=1);

namespace NewApiBundle\Component\Import;

use BadMethodCallException;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\Entity\ImportHouseholdDuplicity;
use NewApiBundle\Entity\ImportQueue;
use NewApiBundle\Enum\ImportDuplicityState;
use NewApiBundle\Enum\ImportQueueState;
use NewApiBundle\Enum\ImportState;
use NewApiBundle\Workflow\ImportTransitions;
use Psr\Log\LoggerInterface;
use Symfony\Component\Workflow\WorkflowInterface;
use NewApiBundle\Entity\User;

class DuplicityResolver
{
    use ImportQueueLoggerTrait;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var IdentityChecker
     */
    private $identityChecker;

    /**
     * @var SimilarityChecker
     */
    private $similarityChecker;

    /**
     * @var WorkflowInterface
     */
    private $importQueueStateMachine;

    /**
     * @var WorkflowInterface
     */
    private $importStateMachine;

    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface        $logger,
        IdentityChecker        $identityChecker,
        SimilarityChecker      $similarityChecker,
        WorkflowInterface      $importQueueStateMachine,
        WorkflowInterface      $importStateMachine
    ) {
        $this->em = $entityManager;
        $this->logger = $logger;
        $this->identityChecker = $identityChecker;
        $this->similarityChecker = $similarityChecker;
        $this->importQueueStateMachine = $importQueueStateMachine;
        $this->importStateMachine = $importStateMachine;
    }

    /**
     * @param ImportQueue $importQueue
     * @param int|null    $acceptedDuplicityId
     * @param string      $status
     * @param User        $user
     */
    public function resolve(ImportQueue $importQueue, ?int $acceptedDuplicityId, string $status, User $user)
    {
        $import = $importQueue->getImport();
        if (!in_array($import->getState(), [
            ImportState::IDENTITY_CHECKING,
            ImportState::IDENTITY_CHECK_CORRECT,
            ImportState::IDENTITY_CHECK_FAILED,
            ImportState::SIMILARITY_CHECKING,
            ImportState::SIMILARITY_CHECK_CORRECT,
            ImportState::SIMILARITY_CHECK_FAILED,
        ])) {
            throw new BadMethodCallException('Unable to execute duplicity resolver. Import is not ready to duplicity resolve.');
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
                        $updates[] = '#'.$duplicity->getId();
                        break;
                    case ImportQueueState::TO_LINK:
                        $duplicity->setState(ImportDuplicityState::DUPLICITY_KEEP_THEIRS);
                        $links[] = '#'.$duplicity->getId();
                        break;
                }

            } else {
                $duplicity->setState(ImportDuplicityState::NO_DUPLICITY);
                $uniques[] = '#'.$duplicity->getId();
            }

            $duplicity->setDecideBy($user);
            $duplicity->setDecideAt(new DateTime());
        }
        $this->importQueueStateMachine->apply($importQueue, $status);
        if (!empty($updates)) {
            $this->logQueueInfo($importQueue,
                "Duplicity suspect(s) [".implode(', ', $updates)."] was resolved as more current duplicity");
        } else {
            $this->logQueueDebug($importQueue, "[Queue #{$importQueue->getId()}] Nothing was resolved as more current duplicity");
        }
        if (!empty($links)) {
            $this->logQueueInfo($importQueue,
                "Duplicity suspect(s) [".implode(', ', $updates)."] was resolved as older duplicity");
        } else {
            $this->logQueueDebug($importQueue, "Nothing was resolved as older duplicity");
        }
        if (!empty($uniques)) {
            $this->logQueueInfo($importQueue,
                "Duplicity suspect(s) [".implode(', ', $updates)."] was resolved as mistake and will be inserted");
        } else {
            $this->logQueueDebug($importQueue, "Nothing was resolved as mistake");
        }

        $this->em->flush();

        // check if it is all to decide
        if ($this->importStateMachine->can($importQueue->getImport(), ImportTransitions::RESOLVE_IDENTITY_DUPLICITIES)) {
            $this->importStateMachine->apply($importQueue->getImport(), ImportTransitions::RESOLVE_IDENTITY_DUPLICITIES);
        } elseif ($this->importStateMachine->can($importQueue->getImport(), ImportTransitions::RESOLVE_SIMILARITY_DUPLICITIES)) {
            $this->importStateMachine->apply($importQueue->getImport(), ImportTransitions::RESOLVE_SIMILARITY_DUPLICITIES);
        }

        $this->em->flush();
    }
}
