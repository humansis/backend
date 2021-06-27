<?php
declare(strict_types=1);

namespace NewApiBundle\Command;

use DistributionBundle\Entity\Assistance;
use DistributionBundle\Repository\AssistanceRepository;
use InvalidArgumentException;
use NewApiBundle\Component\WingMoney\ImportService;
use NewApiBundle\Component\WingMoney\ReportParser;
use NewApiBundle\Component\WingMoney\ValueObject\ReportEntry;
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

    /**
     * @var ReportParser
     */
    private $reportParser;

    /**
     * @var ImportService
     */
    private $importService;

    public function __construct(AssistanceRepository $assistanceRepository, ReportParser $reportParser, ImportService $importService, string $name = null)
    {
        parent::__construct($name);

        $this->assistanceRepository = $assistanceRepository;
        $this->reportParser = $reportParser;
        $this->importService = $importService;
    }


    public function configure()
    {
        $this->setName('app:wing-money:import')
            ->addArgument('reportFile', InputArgument::REQUIRED, 'Report file in xlsx format')
            ->addArgument('assistance', InputArgument::REQUIRED, 'ID of an assistance in which the transactions will be imported')
            ->addOption('check');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $reportFile = $this->getReportFilePath($input);
        $assistance = $this->getAssistance($input);

        echo 'Parsing file "'.$reportFile.'"'.PHP_EOL;

        $entries = $this->reportParser->parseEntries($reportFile);

        $totalEntries = count($entries);
        echo 'Found '.$totalEntries.' valid entries in given file'.PHP_EOL;

        $newEntries = $this->importService->filterExistingTransactions($entries);
        echo 'Found '.count($newEntries).' transactions which are not in system'.PHP_EOL;

        $entriesBelongingToGivenAssistance = $this->importService->filterTransactionsInAssistanceOnly($newEntries, $assistance);
        echo 'Found '.count($entriesBelongingToGivenAssistance).' transactions which belongs to given assistance ('.$assistance->getName().')'.PHP_EOL;

        /** @var ReportEntry $entry */
        foreach ($entriesBelongingToGivenAssistance as $entry) {
            if (!$input->getOption('check')) {
                $this->importService->createTransactionFromReportEntry($entry, $assistance);
            }
        }

        if ($input->getOption('check')) {
            echo PHP_EOL.'None entries were imported.'.PHP_EOL;
        } else {
            echo PHP_EOL.'Entries were imported'.PHP_EOL;
        }

        return 0;
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
