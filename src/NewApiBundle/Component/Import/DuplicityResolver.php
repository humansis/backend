<?php declare(strict_types=1);

namespace NewApiBundle\Component\Import;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\Entity\ImportHouseholdDuplicity;
use NewApiBundle\Entity\ImportQueue;
use NewApiBundle\Enum\ImportDuplicityState;
use NewApiBundle\Enum\ImportQueueState;
use NewApiBundle\Enum\ImportState;
use NewApiBundle\Workflow\ImportQueueTransitions;
use NewApiBundle\Workflow\ImportTransitions;
use NewApiBundle\Workflow\WorkflowTool;
use Psr\Log\LoggerInterface;
use Symfony\Component\Workflow\WorkflowInterface;
use UserBundle\Entity\User;

class DuplicityResolver
{
    use ImportLoggerTrait;

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

    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface        $logger,
        IdentityChecker        $identityChecker,
        SimilarityChecker      $similarityChecker,
        WorkflowInterface      $importQueueStateMachine
    ) {
        $this->em = $entityManager;
        $this->logger = $logger;
        $this->identityChecker = $identityChecker;
        $this->similarityChecker = $similarityChecker;
        $this->importQueueStateMachine = $importQueueStateMachine;
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
            ImportState::IDENTITY_CHECK_FAILED,
            ImportState::SIMILARITY_CHECK_FAILED,
        ])) {
            throw new \BadMethodCallException('Unable to execute duplicity resolver. Import is not ready to duplicity resolve.');
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
        if (!empty($updates)) {
            $this->logImportInfo($importQueue->getImport(),
                "[Queue #{$importQueue->getId()}] Duplicity suspect(s) [".implode(', ', $updates)."] was resolved as more current duplicity");
        } else {
            $this->logImportDebug($importQueue->getImport(), "[Queue #{$importQueue->getId()}] Nothing was resolved as more current duplicity");
        }
        if (!empty($links)) {
            $this->logImportInfo($importQueue->getImport(),
                "[Queue #{$importQueue->getId()}] Duplicity suspect(s) [".implode(', ', $updates)."] was resolved as older duplicity");
        } else {
            $this->logImportDebug($importQueue->getImport(), "[Queue #{$importQueue->getId()}] Nothing was resolved as older duplicity");
        }
        if (!empty($uniques)) {
            $this->logImportInfo($importQueue->getImport(),
                "[Queue #{$importQueue->getId()}] Duplicity suspect(s) [".implode(', ', $updates)."] was resolved as mistake and will be inserted");
        } else {
            $this->logImportDebug($importQueue->getImport(), "[Queue #{$importQueue->getId()}] Nothing was resolved as mistake");
        }

        $this->em->flush();
    }
}
