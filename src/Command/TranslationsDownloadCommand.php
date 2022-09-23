<?php

declare(strict_types=1);

namespace Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpClient\HttpClient;
use UnexpectedValueException;
use Utils\FileSystem\ZipError;
use ZipArchive;

class TranslationsDownloadCommand extends Command
{
    use ZipError;
    
    /** @var HttpClient $client */
    private $client;

    /** @var OutputInterface */
    private $output;

    /** @var string */
    private $translationsDir;
    
    private $envConfig = [
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
    ];
    
    private $env = 'test';

    public function __construct(
        string $translationsDir
    ) {
        parent::__construct();

        $this->translationsDir = $translationsDir;
        $this->client = HttpClient::create();
        
        //uncomment & update $envConfig to get translations from another server
        //$this->env = 'local';
    }

    protected function configure(): void
    {
        parent::configure();
        $this
            ->setName('translations:download')
            ->setDescription('Download translations from remote environment');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;
        
        $jwt = $this->login();

        //download translations
        $response = $this->client->request('GET', $this->envConfig[$this->env]['url'] . '/web-app/v1/translations-xml', [
            'headers' => [
                'Authorization' => 'Bearer ' . $jwt,
            ]
        ]);

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
            ]),
        ]);

        $statusCode = $response->getStatusCode();
        if ($statusCode >= 400) {
            $this->output->writeln('<error>Login failed</error>');
            throw new \RuntimeException('Request failed with status code '.$statusCode);
        }
        
        $response = $response->toArray();
        $this->output->writeln('login to ' . $this->env .  ' successful');
        
        return $response['token'];
    }
}