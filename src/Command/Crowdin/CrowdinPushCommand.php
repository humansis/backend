<?php

namespace Command\Crowdin;

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
 * https://symfony.com/doc/current/translation.html#translation-providers
 */
class CrowdinPushCommand extends Command
{
    use CrowdinRequestTrait;
    
    /** @var HttpClient $client */
    private $client;

    /** @var string */
    private $crowdinApiKey;

    /** @var int */
    private $crowdinProjectId;

    /** @var OutputInterface */
    private $output;

    /** @var string */
    private $translationsDir;

    public function __construct(
        string $crowdinApiKey,
        string $crowdinProjectId,
        string $translationsDir
    ) {
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
            ->setName('crowdin:push')
            ->setDescription('Upload translation sources to Crowdin');
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

        //get local files with source keys
        $finder->files()->in($this->translationsDir)->name('*.en.xlf');
        if (!$finder->hasResults()) {
            throw new UnexpectedValueException('No translations found');
        }

        //get source files list
        $sourceFiles = $this->makeRequest('GET', '/projects/'.$this->crowdinProjectId.'/files');

        foreach ($finder as $file) {
            $this->output->write('<info>Processing: '.$file->getFilename().'</info>');

            //prepare - upload source file to storage
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

            //get crowdin project file id
            $fileId = null;
            foreach ($sourceFiles['data'] as $sourceFile) {
                if ($sourceFile['data']['name'] === $file->getFilename()) {
                    $fileId = $sourceFile['data']['id'];
                    break;
                }
            }
            $this->output->write('.');

            //file exists, update
            if ($fileId) {
                $uploaded = $this->makeRequest('PUT', '/projects/'.$this->crowdinProjectId.'/files/'.$fileId, [
                    'body' => json_encode([
                        'storageId' => $storage['data']['id'],
                        'updateOption' => 'keep_translations_and_approvals',
                    ], JSON_THROW_ON_ERROR),
                ]);

                $status = $uploaded['headers']['crowdin-api-content-status'][0] ?? 'status undefined';
            }
            //file does not exist, add new
            else {
                $this->makeRequest('POST', '/projects/'.$this->crowdinProjectId.'/files', [
                    'body' => json_encode([
                        'storageId' => $storage['data']['id'],
                        'name' => $file->getFilename(),
                        'type' => 'xliff',
                        'exportOptions' => [
                            'exportPattern' => str_replace('.en.', '.%two_letters_code%.', $file->getFilename())
                        ],
                    ], JSON_THROW_ON_ERROR),
                ]);

                $status = 'added';
            }

            $this->output->writeln('<info> uploaded, '.$status.'</info>');
        }

        return 0;
    }
}