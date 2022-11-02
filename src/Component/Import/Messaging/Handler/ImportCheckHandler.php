<?php

declare(strict_types=1);

namespace Component\Import\Messaging\Handler;

use Doctrine\ORM\EntityManagerInterface;
use Component\Import\ImportLoggerTrait;
use Component\Import\Messaging\Message\ImportCheck;
use Entity\Import;
use Enum\ImportState;
use Event\Subscriber\Import\FinishSubscriber;
use Event\Subscriber\Import\IdentitySubscriber;
use Event\Subscriber\Import\IntegritySubscriber;
use Event\Subscriber\Import\SimilaritySubscriber;
use Repository\ImportRepository;
use Workflow\ImportTransitions;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Workflow\WorkflowInterface;

class ImportCheckHandler implements MessageHandlerInterface
{
    use ImportLoggerTrait;

    public function __construct(
        LoggerInterface $importLogger,
        private readonly WorkflowInterface $importStateMachine,
        private readonly ImportRepository $importRepository,
        private readonly MessageBusInterface $messageBus,
        private readonly EntityManagerInterface $em
    ) {
        $this->logger = $importLogger;
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
     * @return void
     */
    private function checkUpload(Import $import)
    {
        $this->tryTransitions($import, [
            ImportTransitions::CHECK_INTEGRITY,
            ImportTransitions::FAIL_UPLOAD,
        ]);
    }

    /**
     * @return void
     */
    private function checkIntegrity(Import $import)
    {
        if (
            $this->isBlockedByNotCompleted(
                $import,
                ImportTransitions::COMPLETE_INTEGRITY,
                IntegritySubscriber::GUARD_CODE_NOT_COMPLETE
            )
        ) {
            $this->messageBus->dispatch(ImportCheck::checkIntegrityComplete($import), [new DelayStamp(5000)]);
        } else {
            $this->tryTransitions($import, [
                ImportTransitions::FAIL_INTEGRITY,
                ImportTransitions::COMPLETE_INTEGRITY,
            ]);
        }
    }

    /**
     * @return void
     */
    private function checkIdentity(Import $import)
    {
        if (
            $this->isBlockedByNotCompleted(
                $import,
                ImportTransitions::COMPLETE_IDENTITY,
                IdentitySubscriber::GUARD_CODE_NOT_COMPLETE
            )
        ) {
            $this->messageBus->dispatch(ImportCheck::checkIdentityComplete($import), [new DelayStamp(5000)]);
        } else {
            $this->tryTransitions($import, [
                ImportTransitions::FAIL_IDENTITY,
                ImportTransitions::COMPLETE_IDENTITY,
            ]);
        }
    }

    /**
     * @return void
     */
    private function checkSimilarity(Import $import)
    {
        if (
            $this->isBlockedByNotCompleted(
                $import,
                ImportTransitions::COMPLETE_SIMILARITY,
                SimilaritySubscriber::GUARD_CODE_NOT_COMPLETE
            )
        ) {
            $this->messageBus->dispatch(ImportCheck::checkSimilarityComplete($import), [new DelayStamp(5000)]);
        } else {
            $this->tryTransitions($import, [
                ImportTransitions::FAIL_SIMILARITY,
                ImportTransitions::COMPLETE_SIMILARITY,
            ]);
        }
    }

    /**
     * @return void
     */
    private function checkImport(Import $import)
    {
        if (
            $this->isBlockedByNotCompleted(
                $import,
                ImportTransitions::FINISH,
                FinishSubscriber::GUARD_CODE_NOT_COMPLETE
            )
        ) {
            $this->messageBus->dispatch(ImportCheck::checkImportingComplete($import), [new DelayStamp(5000)]);
        } else {
            $this->tryTransitions($import, [
                ImportTransitions::FINISH,
            ]);
        }
    }

    private function isBlockedByNotCompleted(Import $import, string $transition, string $code): bool
    {
        foreach ($this->importStateMachine->buildTransitionBlockerList($import, $transition) as $block) {
            if ($block->getCode() === $code) {
                return true;
            }
        }

        return false;
    }
}
