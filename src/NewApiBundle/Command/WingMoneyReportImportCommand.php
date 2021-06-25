<?php
declare(strict_types=1);

namespace NewApiBundle\Command;

use DistributionBundle\Entity\Assistance;
use DistributionBundle\Repository\AssistanceRepository;
use InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class WingMoneyReportImportCommand extends Command
{
    /**
     * @var AssistanceRepository
     */
    private $assistanceRepository;

    public function __construct(AssistanceRepository $assistanceRepository, string $name = null)
    {
        parent::__construct($name);

        $this->assistanceRepository = $assistanceRepository;
    }


    public function configure()
    {
        $this->setName('app:wing-money:import')
            ->addArgument('reportFile', InputArgument::REQUIRED, 'Report file in xlsx format')
            ->addArgument('assistance', InputArgument::REQUIRED, 'ID of an assistance in which the transactions will be imported');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $reportFile = $this->getReportFilePath($input);
        $assistance = $this->getAssistance($input);

    }


    /**
     * @param InputInterface $input
     *
     * @return string
     */
    private function getReportFilePath(InputInterface $input): string
    {
        $filepath = $input->getArgument('reportFile');
        if (!file_exists($filepath)) {
            throw new InvalidArgumentException('Unable to find source file with Wing Money report for import: '.$filepath);
        }

        return $filepath;
    }


    private function getAssistance(InputInterface $input): Assistance
    {
        $assistanceId = $input->getArgument('assistance');

        /** @var Assistance|null $assistance */
        $assistance = $this->assistanceRepository->find($assistanceId);

        if (!$assistance instanceof Assistance) {
            throw new InvalidArgumentException("Assistance wit ID $assistanceId does not exist");
        }

        return $assistance;
    }
}
