<?php declare(strict_types=1);

namespace NewApiBundle\Component\Import\Messaging\Handler;

use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\Component\Import\ImportLoggerTrait;
use NewApiBundle\Component\Import\Messaging\Message\ImportCheck;
use NewApiBundle\Entity\Import;
use NewApiBundle\Enum\ImportState;
use NewApiBundle\Repository\ImportRepository;
use NewApiBundle\Workflow\ImportTransitions;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Workflow\WorkflowInterface;

class ImportCheckHandler implements MessageHandlerInterface
{
    use ImportLoggerTrait;

    /** @var WorkflowInterface */
    private $importStateMachine;

    /** @var ImportRepository */
    private $importRepository;

    /** @var EntityManagerInterface */
    private $em;

    /**
     * @param LoggerInterface            $importLogger
     * @param WorkflowInterface          $importStateMachine
     * @param ImportRepository           $importRepository
     * @param EntityManagerInterface     $em
     */
    public function __construct(
        LoggerInterface             $importLogger,
        WorkflowInterface           $importStateMachine,
        ImportRepository            $importRepository,
        EntityManagerInterface      $em
    ) {
        $this->logger = $importLogger;
        $this->importStateMachine = $importStateMachine;
        $this->importRepository = $importRepository;
        $this->em = $em;
    }


    public function __invoke(ImportCheck $importCheck): void
    {
        $import = $this->importRepository->find($importCheck->getImportId());
        $this->logImportInfo($import, "Import check message");

        switch ($importCheck->getCheckType()) {
            case ImportState::UPLOADING:
                $this->tryTransitions($import, [
                    ImportTransitions::CHECK_INTEGRITY,
                    ImportTransitions::FAIL_UPLOAD
                ]);
                break;
            case ImportState::INTEGRITY_CHECKING:
                $this->tryTransitions($import, [
                    ImportTransitions::FAIL_INTEGRITY,
                    ImportTransitions::COMPLETE_INTEGRITY
                ]);
                break;
            case ImportState::IDENTITY_CHECKING:
                $this->tryTransitions($import, [
                    ImportTransitions::FAIL_IDENTITY,
                    ImportTransitions::COMPLETE_IDENTITY
                ]);
                break;
            case ImportState::SIMILARITY_CHECKING:
                $this->tryTransitions($import, [
                    ImportTransitions::FAIL_SIMILARITY,
                    ImportTransitions::COMPLETE_SIMILARITY
                ]);
                break;
            case ImportState::IMPORTING:
                $this->tryTransitions($import, [
                    ImportTransitions::FINISH,
                ]);
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
}
