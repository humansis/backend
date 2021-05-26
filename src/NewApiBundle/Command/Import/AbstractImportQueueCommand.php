<?php
declare(strict_types=1);

namespace NewApiBundle\Command\Import;

use Doctrine\Persistence\ObjectManager;
use NewApiBundle\Entity\Import;
use NewApiBundle\Entity\ImportQueue;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractImportQueueCommand extends Command
{
    /** @var Import|null */
    protected $import;
    /** @var ObjectManager */
    protected $manager;

    /**
     * AbstractImportQueueCommand constructor.
     *
     * @param ObjectManager $manager
     */
    public function __construct(ObjectManager $manager)
    {
        parent::__construct();
        $this->manager = $manager;
    }

    protected function configure()
    {
        $this->addArgument('import', InputArgument::OPTIONAL, 'Filter queue by Import (ID or title)');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->hasArgument('import')) {
            $byId = $this->manager->getRepository(Import::class)->find($input->getArgument('import'));
            $byTitle = $this->manager->getRepository(Import::class)->findOneBy([
                'title' => $input->getArgument('import'),
            ]);
            if ($byId) {
                $this->import = $byId;
            }
            if ($byTitle) {
                $this->import = $byTitle;
            } else {
                throw new \InvalidArgumentException('Argument Import must be ID or title of existing Import. No such found.');
            }
        }
    }

    protected function getQueue(array $statuses): iterable
    {
        if ($this->import) {
            return $this->manager->getRepository(ImportQueue::class)->findBy([
                'state' => $statuses,
                'import' => $this->import->getId(),
            ], [
                'id' => 'asc',
            ]);
        } else {
            return $this->manager->getRepository(ImportQueue::class)->findBy([
                'state' => $statuses,
            ], [
                'id' => 'asc',
            ]);
        }

    }

}
