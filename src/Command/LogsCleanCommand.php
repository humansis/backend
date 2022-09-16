<?php
declare(strict_types=1);

namespace Command;

use Component\LogsStorage\LogsStorageService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LogsCleanCommand extends Command
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var LogsStorageService
     */
    private $logsStorageService;

    public function __construct(LoggerInterface $logger, LogsStorageService $logsStorageService)
    {
        parent::__construct();

        $this->logger = $logger;
        $this->logsStorageService = $logsStorageService;
    }

    protected function configure()
    {
        parent::configure();
        $this
            ->setName('aws:logs:clean')
            ->setDescription('Clean all old logs sent by vendor app or field app')
        ;
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
