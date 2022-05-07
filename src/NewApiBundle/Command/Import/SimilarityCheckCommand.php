<?php
declare(strict_types=1);

namespace NewApiBundle\Command\Import;

use Doctrine\Persistence\ObjectManager;
use NewApiBundle\Component\Import\ImportService;
use NewApiBundle\Entity\Import;
use NewApiBundle\Enum\ImportState;
use NewApiBundle\Workflow\ImportTransitions;
use NewApiBundle\Workflow\WorkflowTool;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Workflow\WorkflowInterface;
use Throwable;

class SimilarityCheckCommand extends AbstractImportQueueCommand
{
    protected function configure()
    {
        parent::configure();
        $this
            ->setName('app:import:similarity')
            ->setDescription('Run similarity duplicity check on import');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        if (is_null($this->imports)) {
            $imports = [$this->imports];
        } else {
            $imports = $this->manager->getRepository(Import::class)
                ->findBy([
                    'state' => ImportState::SIMILARITY_CHECKING,
                ]);
        }

        if (!empty($this->imports)) {
            $this->logAffectedImports($this->imports, 'app:import:similarity');
        } else {
            $this->logger->debug('app:import:similarity affects no imports');
        }

        $output->write($this->getName()." finding duplicities in ".count($this->imports)." imports ");

        /** @var Import $import */
        foreach ($imports as $import) {
            try {
                $this->tryTransitions($import, [
                    ImportTransitions::FAIL_SIMILARITY,
                    ImportTransitions::COMPLETE_SIMILARITY
                ]);
                $this->manager->flush();

                if (ImportState::SIMILARITY_CHECK_CORRECT === $import->getState()) {
                    $this->logImportDebug($import, "Similarity check found no duplicities");
                } else {
                    $statistics = $this->importService->getStatistics($import);
                    $this->logImportInfo($import, "Similarity check found {$statistics->getAmountIdentityDuplicities()} duplicities");
                }
            } catch (Throwable $e) {
                $this->logImportError($import, 'Unknown Exception in similarity check occurred. Exception message: '.$e->getMessage());
            }

        }

        $this->manager->flush();

        $output->writeln('Done');

        return 0;
    }
}
