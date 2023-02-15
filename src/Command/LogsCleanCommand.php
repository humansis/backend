<?php

declare(strict_types=1);

namespace Command;

use Component\LogsStorage\LogsStorageService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'aws:logs:clean')]
class LogsCleanCommand extends Command
{
    public function __construct(private readonly LoggerInterface $logger, private readonly LogsStorageService $logsStorageService)
    {
        parent::__construct();
    }

    protected function configure()
    {
        parent::configure();
        $this->setDescription('Clean all old logs sent by vendor app or field app');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $clearedLogs = $this->logsStorageService->clearOldLogs();

        $logMessage = count($clearedLogs) . ' files with logs were cleared in total.';

        if (count($clearedLogs) > 0) {
            $logMessage .= ' List of logs cleared: [ "' . implode('", "', $clearedLogs) . '" ]';
        }

        $this->logger->info($logMessage);

        return 0;
    }
}
