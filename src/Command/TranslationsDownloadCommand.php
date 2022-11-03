<?php

declare(strict_types=1);

namespace Command;

use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpClient\HttpClient;
use UnexpectedValueException;
use Utils\FileSystem\ZipError;
use ZipArchive;

class TranslationsDownloadCommand extends Command
{
    protected static $defaultName = 'translations:download';
    use ZipError;

    private readonly \Symfony\Component\HttpClient\HttpClient $client;

    private ?\Symfony\Component\Console\Output\OutputInterface $output = null;

    private array $envConfig = [
        'local' => [
            'url' => 'http://172.17.0.1:8087/api/jwt',
            'password' => 'pin1234',
            'username' => 'admin@example.org',
        ],
        'test' => [
            'url' => 'https://apitest.humansis.org/api/jwt',
            'password' => 'pin1234',
            'username' => 'admin@example.org',
        ],
        'dev1' => [
            'url' => 'https://apidev.humansis.org/api/jwt',
            'password' => 'pin1234',
            'username' => 'admin@example.org',
        ],
        'dev2' => [
            'url' => 'https://apidev2.humansis.org/api/jwt',
            'password' => 'pin1234',
            'username' => 'admin@example.org',
        ],
        'dev3' => [
            'url' => 'https://apidev3.humansis.org/api/jwt',
            'password' => 'pin1234',
            'username' => 'admin@example.org',
        ],
        'stage' => [
            'url' => 'https://apistage.humansis.org/api/jwt',
            'password' => 'pin1234',
            'username' => 'admin@example.org',
        ],
    ];

    private string $env = 'test';

    public function __construct(
        private readonly string $translationsDir
    ) {
        parent::__construct();
        $this->client = new HttpClient();
    }

    protected function configure(): void
    {
        parent::configure();
        $this->setDescription('Download translations from remote environment')
            ->addArgument('env', InputArgument::OPTIONAL, 'which environment (default is test)?');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;

        $this->env = $input->getArgument('env') ?? 'test';

        $jwt = $this->login();

        //download translations
        $response = $this->client->request(
            'GET',
            $this->envConfig[$this->env]['url'] . '/web-app/v1/translations-xml',
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $jwt,
                ],
            ]
        );

        $statusCode = $response->getStatusCode();
        if ($statusCode >= 400) {
            $this->output->writeln('<error>Translations download failed with code ' . $statusCode . '</error>');
            $this->output->writeln($response->getContent());

            return 1;
        }

        $filesystem = new Filesystem();
        $filesystem->mkdir($this->translationsDir . '/temp');

        file_put_contents($this->translationsDir . '/temp/translations.zip', $response->getContent());

        $this->output->writeln('translations downloaded');

        //unpack translations
        $zip = new ZipArchive();
        $res = $zip->open($this->translationsDir . '/temp/translations.zip');

        if ($res !== true) {
            throw new UnexpectedValueException('Cannot open zip file - ' . $this->getZipError($res));
        }

        $zip->extractTo($this->translationsDir);
        $zip->close();
        $filesystem->remove($this->translationsDir . '/temp');
        $this->output->writeln('translations unpacked');

        return 0;
    }

    private function login(): string
    {
        $response = $this->client->request('POST', $this->envConfig[$this->env]['url'] . '/web-app/v1/login', [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode([
                'password' => $this->envConfig[$this->env]['password'],
                'username' => $this->envConfig[$this->env]['username'],
            ], JSON_THROW_ON_ERROR),
        ]);

        $statusCode = $response->getStatusCode();
        if ($statusCode >= 400) {
            $this->output->writeln('<error>Login failed</error>');
            throw new RuntimeException('Request failed with status code ' . $statusCode);
        }

        $response = $response->toArray();
        $this->output->writeln('login to ' . $this->env . ' successful');

        return $response['token'];
    }
}
