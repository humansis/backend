<?php

declare(strict_types=1);

namespace Command;

use Entity\Assistance;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Component\WingMoney\ImportService;
use Component\WingMoney\ReportParser;
use Component\WingMoney\ValueObject\ReportEntry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Entity\User;

#[AsCommand(name: 'app:wing-money:import')]
class WingMoneyReportImportCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ReportParser $reportParser,
        private readonly ImportService $importService,
        string $name = null
    ) {
        parent::__construct($name);
    }

    public function configure()
    {
        $this->addArgument('reportFile', InputArgument::REQUIRED, 'Report file in xlsx format')
            ->addArgument(
                'assistance',
                InputArgument::REQUIRED,
                'ID of an assistance in which the transactions will be imported'
            )
            ->addArgument('user', InputArgument::REQUIRED, 'ID of an user. Will be saved as sendBy for transactions.')
            ->addOption('check');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $reportFile = $this->getReportFilePath($input);
        $assistance = $this->getAssistance($input);
        $user = $this->getUser($input);

        $output->writeln('Parsing file "' . $reportFile . '"');

        $entries = $this->reportParser->parseEntries($reportFile);

        $totalEntries = count($entries);
        $output->writeln('Found ' . $totalEntries . ' valid entries in given file');

        $newEntries = $this->importService->filterExistingTransactions($entries);
        $totalInSystem = $totalEntries - count($newEntries);

        $output->writeln($totalInSystem . ' entries are already in system');

        $entriesToImport = $this->importService->filterTransactionsInAssistanceOnly($newEntries, $assistance);

        $output->writeln(
            count($newEntries) - count(
                $entriesToImport
            ) . ' entries won\'t be imported (phone number not found, phone number does not belong to beneficiary in given assistance, amount does not match with assistance commodity)'
        );
        $output->writeln(count($entriesToImport) . ' entries ready to import');

        /** @var ReportEntry $entry */
        foreach ($entriesToImport as $entry) {
            if (!$input->getOption('check')) {
                $this->importService->createTransactionFromReportEntry($entry, $assistance, $user);
            }
        }

        $output->writeln('');
        if ($input->getOption('check')) {
            $output->writeln('None entries were imported.');
        } else {
            $output->writeln('Entries were imported');
        }

        return 0;
    }

    private function getReportFilePath(InputInterface $input): string
    {
        $filepath = $input->getArgument('reportFile');
        if (!file_exists($filepath)) {
            throw new InvalidArgumentException(
                'Unable to find source file with Wing Money report for import: ' . $filepath
            );
        }

        return $filepath;
    }

    private function getAssistance(InputInterface $input): Assistance
    {
        $assistanceId = $input->getArgument('assistance');

        /** @var Assistance|null $assistance */
        $assistance = $this->em->getRepository(Assistance::class)->find($assistanceId);

        if (!$assistance instanceof Assistance) {
            throw new InvalidArgumentException("Assistance wit ID $assistanceId does not exist");
        }

        return $assistance;
    }

    private function getUser(InputInterface $input): User
    {
        $userId = $input->getArgument('user');

        /** @var User|null $user */
        $user = $this->em->getRepository(User::class)->find($userId);

        if (!$user instanceof User) {
            throw new InvalidArgumentException("User wit ID $userId does not exist");
        }

        return $user;
    }
}
