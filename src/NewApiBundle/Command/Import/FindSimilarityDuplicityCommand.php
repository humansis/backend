<?php
declare(strict_types=1);

namespace NewApiBundle\Command\Import;

use NewApiBundle\Entity\Import;
use NewApiBundle\Enum\ImportState;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FindSimilarityDuplicityCommand extends AbstractImportQueueCommand
{
    protected function configure()
    {
        $this
            ->setName('app:import:similarity')
            ->setDescription('Run similarity duplicity check on import')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        if (is_null($this->import)) {
            $imports = [$this->import];
        } else {
            $imports = $this->manager->getRepository(Import::class)
                ->findBy([
                    'state' => ImportState::SIMILARITY_CHECKING,
                ]);
        }

        $output->writeln([
            "Similarity check",
            count($imports)." imports in queue",
        ]);

        /** @var Import $import */
        foreach ($imports as $import) {
            //TODO similarty check

            $import->setState(ImportState::SIMILARITY_CHECK_CORRECT);
        }

        $this->manager->flush();

        $output->writeln('Similarity check completed');
    }
}
