<?php declare(strict_types=1);

namespace NewApiBundle\Component\Import\Messaging\Handler;

use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\Component\Import\ImportLoggerTrait;
use NewApiBundle\Component\Import\Messaging\Message\ImportCheck;
use NewApiBundle\Entity\Import;
use NewApiBundle\Enum\ImportState;
use NewApiBundle\Event\Subscriber\Import\FinishSubscriber;
use NewApiBundle\Event\Subscriber\Import\IdentitySubscriber;
use NewApiBundle\Event\Subscriber\Import\IntegritySubscriber;
use NewApiBundle\Event\Subscriber\Import\SimilaritySubscriber;
use NewApiBundle\Repository\ImportRepository;
use NewApiBundle\Workflow\ImportTransitions;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Workflow\WorkflowInterface;

class ImportCheckHandler implements MessageHandlerInterface
{
    use ImportLoggerTrait;

    /** @var WorkflowInterface */
    private $importStateMachine;

    /** @var ImportRepository */
    private $importRepository;

    /** @var MessageBusInterface */
    private $messageBus;

    /** @var EntityManagerInterface */
    private $em;

    /**
     * @param LoggerInterface        $importLogger
     * @param WorkflowInterface      $importStateMachine
     * @param ImportRepository       $importRepository
     * @param MessageBusInterface    $messageBus
     * @param EntityManagerInterface $em
     */
    public function __construct(
        LoggerInterface             $importLogger,
        WorkflowInterface           $importStateMachine,
        ImportRepository            $importRepository,
        MessageBusInterface         $messageBus,
        EntityManagerInterface      $em
    ) {
        $this->logger = $importLogger;
        $this->importStateMachine = $importStateMachine;
        $this->importRepository = $importRepository;
        $this->messageBus = $messageBus;
        $this->em = $em;
    }


    public function __invoke(ImportCheck $importCheck): void
    {
        $import = $this->importRepository->find($importCheck->getImportId());
        $this->logImportInfo($import, "Import check message");

        switch ($importCheck->getCheckType()) {
            case ImportState::UPLOADING:
                $this->checkUpload($import);
                break;
            case ImportState::INTEGRITY_CHECKING:
                $this->checkIntegrity($import);
                break;
            case ImportState::IDENTITY_CHECKING:
                $this->checkIdentity($import);
                break;
            case ImportState::SIMILARITY_CHECKING:
                $this->checkSimilarity($import);
                break;
            case ImportState::IMPORTING:
                $this->checkImport($import);
                break;
        }
    }



    protected function tryTransitions(Import $import, array $transitions): void
    {
        $transitionNames = implode("', '", $transitions);
        $this->logImportInfo($import, "is checked to transitions '$transitionNames'");
        foreach ($transitions as $transition) {
            if ($this->importStateMachine->can($import, $transition)) {
                $this->logImportInfo($import, "is going to '$transition'");
                $this->importStateMachine->apply($import, $transition);
                $this->em->flush();
                return;
            } else {
                $this->logImportTransitionConstraints($this->importStateMachine, $import, $transition);
            }
        }
    }

    /**
     * @param Import $import
     *
     * @return void
     */
    private function checkUpload(Import $import)
    {
        $this->tryTransitions($import, [
            ImportTransitions::CHECK_INTEGRITY,
            ImportTransitions::FAIL_UPLOAD
        ]);
    }

    /**
     * @param Import $import
     *
     * @return void
     */
    private function checkIntegrity(Import $import)
    {
        if ($this->isBlockedByNotCompleted($import, ImportTransitions::COMPLETE_INTEGRITY, IntegritySubscriber::GUARD_CODE_NOT_COMPLETE)) {
            $this->messageBus->dispatch(ImportCheck::checkIntegrityComplete($import), [new DelayStamp(5000)]);
        } else {
            $this->tryTransitions($import, [
                ImportTransitions::FAIL_INTEGRITY,
                ImportTransitions::COMPLETE_INTEGRITY
            ]);
        }
    }

    /**
     * @param Import $import
     *
     * @return void
     */
    private function checkIdentity(Import $import) {
        if ($this->isBlockedByNotCompleted($import, ImportTransitions::COMPLETE_IDENTITY, IdentitySubscriber::GUARD_CODE_NOT_COMPLETE)) {
            $this->messageBus->dispatch(ImportCheck::checkIdentityComplete($import), [new DelayStamp(5000)]);
        } else {
            $this->tryTransitions($import, [
                ImportTransitions::FAIL_IDENTITY,
                ImportTransitions::COMPLETE_IDENTITY
            ]);
        }

    }

    /**
     * @param Import $import
     *
     * @return void
     */
    private function checkSimilarity(Import $import) {
        if ($this->isBlockedByNotCompleted($import, ImportTransitions::COMPLETE_SIMILARITY, SimilaritySubscriber::GUARD_CODE_NOT_COMPLETE)) {
            $this->messageBus->dispatch(ImportCheck::checkSimilarityComplete($import), [new DelayStamp(5000)]);
        } else {
            $this->tryTransitions($import, [
                ImportTransitions::FAIL_SIMILARITY,
                ImportTransitions::COMPLETE_SIMILARITY
            ]);
        }
    }

    /**
     * @param Import $import
     *
     * @return void
     */
    private function checkImport(Import $import) {
        if ($this->isBlockedByNotCompleted($import, ImportTransitions::FINISH, FinishSubscriber::GUARD_CODE_NOT_COMPLETE)) {
            $this->messageBus->dispatch(ImportCheck::checkImportingComplete($import), [new DelayStamp(5000)]);
        } else {
            $this->tryTransitions($import, [
                ImportTransitions::FINISH,
            ]);
        }

    }

    /**
     * @param Import $import
     * @param string $transition
     * @param string $code
     *
     * @return bool
     */
    private function isBlockedByNotCompleted(Import $import,string $transition,string $code): bool {
        foreach ($this->importStateMachine->buildTransitionBlockerList($import, $transition) as $block) {
            if ($block->getCode() === $code) {
                return true;
            }
        }
        return false;
    }
}
