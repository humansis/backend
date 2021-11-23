<?php declare(strict_types=1);

namespace NewApiBundle\Component\Import;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\Entity\ImportBeneficiaryDuplicity;
use NewApiBundle\Entity\ImportQueue;
use NewApiBundle\Enum\ImportDuplicityState;
use NewApiBundle\Enum\ImportQueueState;
use NewApiBundle\Enum\ImportState;
use NewApiBundle\InputType\DuplicityResolveInputType;
use NewApiBundle\Workflow\Exception\WorkflowException;
use NewApiBundle\Workflow\ImportTransitions;
use Psr\Log\LoggerInterface;
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

    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface        $logger,
        IdentityChecker        $identityChecker,
        SimilarityChecker      $similarityChecker
    ) {
        $this->em = $entityManager;
        $this->logger = $logger;
        $this->identityChecker = $identityChecker;
        $this->similarityChecker = $similarityChecker;
    }

    /**
     * @param ImportQueue $importQueue
     * @param DuplicityResolveInputType $inputType
     * @param User $user
     */
    public function resolve(ImportQueue $importQueue, DuplicityResolveInputType $inputType, User $user)
    {
        //TODO find transition by $inputType->getStatus() (now end status = transition name)

        /** @var ImportBeneficiaryDuplicity[] $duplicities */
        $duplicities = $this->em->getRepository(ImportBeneficiaryDuplicity::class)->findBy([
            'ours' => $importQueue,
        ]);

        $updates = [];
        $links = [];
        $uniques = [];
        foreach ($duplicities as $duplicity) {
            if ($duplicity->getId() === $inputType->getAcceptedDuplicityId()) {

                switch ($inputType->getStatus()) {
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

        $import = $importQueue->getImport();

        $this->em->flush();

        switch ($import->getState()) {
            case ImportState::IDENTITY_CHECK_FAILED:
                if (!$this->identityChecker->isImportQueueSuspicious($import)) {
                    $importTransition = ImportTransitions::COMPLETE_IDENTITY;
                    $import->setState(ImportState::IDENTITY_CHECK_CORRECT);
                }
                break;
            case ImportState::SIMILARITY_CHECK_FAILED:
                if (!$this->similarityChecker->isImportQueueSuspicious($import)) {
                    $importTransition = ImportTransitions::COMPLETE_SIMILARITY;
                    $import->setState(ImportState::SIMILARITY_CHECK_CORRECT);
                }
        }

        if (isset($importTransition)) {
            if ($this->importStateMachine->can($import, $importTransition)) {
                $this->importStateMachine->apply($import, $importTransition);
            } else {
                throw new WorkflowException('Import is in invalid state');
            }
        }

        $this->em->flush();
    }
}
