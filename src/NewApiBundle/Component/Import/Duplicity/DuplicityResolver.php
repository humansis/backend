<?php declare(strict_types=1);

namespace NewApiBundle\Component\Import\Duplicity;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\Component\Import\Entity\BeneficiaryDuplicity;
use NewApiBundle\Component\Import\Entity\Queue;
use NewApiBundle\Component\Import\Enum\DuplicityState;
use NewApiBundle\Component\Import\Enum\QueueState;
use NewApiBundle\Component\Import\Enum\State;
use NewApiBundle\Component\Import\Utils\ImportLoggerTrait;
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
     * @param Queue $importQueue
     * @param int         $duplicityId
     * @param string      $status
     * @param User        $user
     */
    public function resolve(Queue $importQueue, int $duplicityId, string $status, User $user)
    {
        $import = $importQueue->getImport();
        if (!in_array($import->getState(), [
            State::IDENTITY_CHECK_FAILED,
            State::SIMILARITY_CHECK_FAILED,
        ])) {
            throw new \BadMethodCallException('Unable to execute duplicity resolver. Import is not ready to duplicity resolve.');
        }

        /** @var BeneficiaryDuplicity[] $duplicities */
        $duplicities = $this->em->getRepository(BeneficiaryDuplicity::class)->findBy([
            'ours' => $importQueue,
        ]);

        $updates = [];
        $links = [];
        $uniques = [];
        foreach ($duplicities as $duplicity) {
            if ($duplicity->getId() === $duplicityId) {

                switch ($status) {
                    case QueueState::TO_UPDATE:
                        $duplicity->setState(DuplicityState::DUPLICITY_KEEP_OURS);
                        $updates[] = '#'.$duplicity->getId();
                        break;
                    case QueueState::TO_LINK:
                        $duplicity->setState(DuplicityState::DUPLICITY_KEEP_THEIRS);
                        $links[] = '#'.$duplicity->getId();
                        break;
                }

            } else {
                $duplicity->setState(DuplicityState::NO_DUPLICITY);
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
