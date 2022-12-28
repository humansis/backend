<?php

declare(strict_types=1);

namespace Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
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
    private const ENVIRONMENT = 'https://apistage.humansis.org';
    private const JWT_TOKEN = '';
    private const SSL_CERT_PATH = '/cert.pem';
    private const SSL_PASSPHRASE = '';

    private readonly HttpClientInterface $client;

    public function __construct(
        private readonly string $logsDir,
        private readonly string $projectDir,
    )
    {
        parent::__construct();

        $this->client = HttpClient::create();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $files = glob($this->logsDir . '/*.json');

        if (empty($files)) {
            $output->writeln('No transactions to upload');
            return Command::SUCCESS;
        }

        foreach ($files as $file) {
            $fileName = basename($file, '.json');

            $output->write('Uploading ' . $fileName . '... ');

            $content = file_get_contents($file);

            $filenameParts = explode('-', $fileName);
            $smartcardCode = end($filenameParts);

            $options = [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'body' => $content,
                'auth_bearer' => self::JWT_TOKEN,
                'local_cert' => $this->projectDir.self::SSL_CERT_PATH,
                'passphrase' => self::SSL_PASSPHRASE,
            ];

            $response = $this->client->request(
                'POST',
                self::ENVIRONMENT . "/api/jwt/vendor-app/v4/smartcards/$smartcardCode/purchase",
                $options
            );

            $output->writeln((string) $response->getStatusCode());
        }

        return Command::SUCCESS;
    }
}
