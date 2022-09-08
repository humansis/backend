<?php

namespace NewApiBundle\Command\Crowdin;

use JsonException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use UnexpectedValueException;

/**
 * Upload source files to Crowdin.
 * @deprecated use official crowdin package after upgrading symfony to 6.x
 */
class CrowdinUpdateCommand extends Command
{
    /** @var HttpClient $client */
    private $client;

    /** @var string */
    private $crowdinApiKey;
    
    /** @var int  */
    private $crowdinProjectId;

    /** @var OutputInterface */
    private $output;

    /** @var string */
    private $translationsDir;

    public function __construct(
        string $crowdinApiKey,
        string $crowdinProjectId,
        string $translationsDir
    )
    {
        parent::__construct();

        $this->crowdinApiKey = $crowdinApiKey;
        $this->crowdinProjectId = $crowdinProjectId;
        $this->translationsDir = $translationsDir;

        $this->client = HttpClient::create();
    }

    protected function configure(): void
    {
        parent::configure();
        $this
            ->setName('crowdin:update')
            ->setDescription('Upload translations to Crowdin');
    }

    /**
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws JsonException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;
        $finder = new Finder();

        //get local translations
        $finder->files()->in($this->translationsDir)->name('*.en.xlf');
        if (!$finder->hasResults()) {
            throw new UnexpectedValueException('No translations found');
        }

        //get source files list
        $sourceFiles = $this->makeRequest('GET', '/projects/'.$this->crowdinProjectId.'/files');

        foreach ($finder as $file) {
            $this->output->write('<info>Processing: '.$file->getFilename().'</info>');

            //get file id
            $fileId = null;
            foreach ($sourceFiles['data'] as $sourceFile) {
                if ($sourceFile['data']['name'] === $file->getFilename()) {
                    $fileId = $sourceFile['data']['id'];
                    break;
                }
            }
            if (!$fileId) {
                //todo add new file
                $this->output->writeln(
                    '<error>file not found in Crowdin (project id: '.$this->crowdinProjectId.')</error>'
                );
            }
            $this->output->write(' .');

            //upload file to storage
            $storage = $this->makeRequest('POST', '/storages', [
                'headers' => [
                    'Content-Type' => 'application/xliff+xml',
                    'Crowdin-API-FileName' => $file->getFilename(),
                ],
                'body' => $file->getContents(),
            ]);

            if (!isset($storage['data']['id'])) {
                throw new UnexpectedValueException('No storage id found');
            }
            $this->output->write('.');

            $uploaded = $this->makeRequest('PUT', '/projects/'.$this->crowdinProjectId.'/files/'.$fileId, [
                'body' => json_encode([
                    'storageId' => $storage['data']['id'],
                    'updateOption' => 'keep_translations_and_approvals',
                ], JSON_THROW_ON_ERROR),
            ]);

            $status = $uploaded['headers']['crowdin-api-content-status'][0] ?? 'status undefined';

            $this->output->writeln('<info> uploaded, '.$status.'</info>');
        }

        return 0;
    }

    /**
     * @throws TransportExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws JsonException
     */
    private function makeRequest(string $method, string $endpoint, array $options = []): array
    {
        $url = 'https://api.crowdin.com/api/v2'.$endpoint;

        $options = array_merge_recursive([
            'headers' => [
                'Authorization' => 'Bearer ' . $this->crowdinApiKey,
            ],
        ], $options);

        if (!isset($options['headers']['Content-Type'])) { //array_merge_recursive would add instead of replace
            $options['headers']['Content-Type'] = 'application/json';
        }

        //$this->output->writeln('<info>'.var_dump($options).'</info>');

        $response = $this->client->request($method, $url, $options);
        $statusCode = $response->getStatusCode();
        $content = $response->getContent(false);
        $headers = $response->getHeaders(false);

        if ($statusCode >= 400) {
            $this->output->writeln('<error>'.$method.' '.$url."\n".$statusCode.': '.$content.'</error>');
            throw new \RuntimeException('Request failed with status code '.$statusCode);
        }

        return array_merge(
            ['headers' => $headers],
            json_decode($content, true, 512, JSON_THROW_ON_ERROR)
        );
    }
}