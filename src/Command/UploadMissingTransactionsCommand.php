<?php

declare(strict_types=1);

namespace Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'missing-transactions:upload',
    description: 'This command tries to upload transaction that ended up in logs because of some failure in sync process.
        Because this is only for temporary usage, it is very simple. You need set environment where should be purchases uploaded, provide JWT of logged user.
        Then specify path to your ssl certificate and passphrase.',
)]
class UploadMissingTransactionsCommand extends Command
{
    private const SSL_CERT_PATH = '/cert.pem';

    private readonly HttpClientInterface $client;

    public function __construct(
        private readonly string $logsDir,
        private readonly string $projectDir,
    ) {
        parent::__construct();

        $this->client = HttpClient::create();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $environment = $this->getHelper('question')->ask(
            $input,
            $output,
            new Question(
                'Environment URL [https://api.humansis.org]: ',
                'https://api.humansis.org'
            )
        );

        $jwtToken = $this->getHelper('question')->ask(
            $input,
            $output,
            new Question(
                'JWT token: '
            )
        );

        $sslPassphrase = $this->getHelper('question')->ask(
            $input,
            $output,
            new Question(
                'SSL Passphrase: '
            )
        );

        $files = glob($this->logsDir . '/*.json');

        if (empty($files)) {
            $output->writeln('No transactions to upload');
            return Command::SUCCESS;
        }

        $progressBar = new ProgressBar($output, count($files));
        $progressBar->start();

        foreach ($files as $file) {
            $fileName = basename($file, '.json');

            $output->write(' ' . $fileName . '... ');

            $content = file_get_contents($file);

            $filenameParts = explode('-', $fileName);
            $smartcardCode = end($filenameParts);

            $options = [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'body' => $content,
                'auth_bearer' => $jwtToken,
                'local_cert' => $this->projectDir . self::SSL_CERT_PATH,
                'passphrase' => $sslPassphrase,
            ];

            $response = $this->client->request(
                'POST',
                "$environment/api/jwt/vendor-app/v4/smartcards/$smartcardCode/purchase",
                $options
            );

            $output->writeln((string) $response->getStatusCode());

            $progressBar->advance();
        }

        $progressBar->finish();
        $output->writeln('');

        return Command::SUCCESS;
    }
}
