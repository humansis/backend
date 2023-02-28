<?php

declare(strict_types=1);

namespace Command\Crowdin;

use JsonException;
use Exception\CrowdinBuildTimeoutException;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use UnexpectedValueException;
use Utils\FileSystem\ZipError;
use ZipArchive;

/**
 * Upload source files to Crowdin.
 *
 * @deprecated use official crowdin package after upgrading symfony to 6.x
 * https://symfony.com/doc/current/translation.html#translation-providers
 */
class CrowdinPullCommand extends Command
{
    use CrowdinRequestTrait;
    use ZipError;

    protected static $defaultName = 'crowdin:pull';


    //If set on true, the source english files will be downloaded from Crowdin.
    //You may want to set it to false if you made changes in versioned en source files.
    private const PULL_SOURCE_FILES = true;

    /** @var HttpClient $client */
    private $client;

    /** @var OutputInterface */
    private $output;

    public function __construct(
        private readonly string $crowdinApiKey,
        private readonly string $crowdinProjectId,
        private readonly string $translationsDir
    ) {
        parent::__construct();

        $this->client = HttpClient::create();
    }

    protected function configure(): void
    {
        parent::configure();
        $this->setDescription('Get translations from Crowdin');
    }

    /**
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws JsonException
     * @throws CrowdinBuildTimeoutException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;

        if (self::PULL_SOURCE_FILES) {
            $this->downloadSourceFiles();
        }

        //init build
        $this->output->write('Initializing build');
        $response = $this->makeRequest('POST', '/projects/' . $this->crowdinProjectId . '/translations/builds', [
            'body' => json_encode([
                'skipUntranslatedStrings' => false,
            ], JSON_THROW_ON_ERROR),
        ]);

        if (!isset($response['data']['id'], $response['data']['status'])) {
            throw new UnexpectedValueException('Build not initialized (missing build id or status)');
        }

        //poll build status
        $buildId = $response['data']['id'];
        $status = $response['data']['status'];
        $clock = 0;

        while ($status !== 'finished') {
            if ($clock > 30) {
                throw new CrowdinBuildTimeoutException('Build not finished after 30 seconds');
            }

            sleep(1);
            $this->output->write('.');
            $response = $this->makeRequest(
                'GET',
                '/projects/' . $this->crowdinProjectId . '/translations/builds/' . $buildId
            );
            $status = $response['data']['status'];
            $clock++;
        }

        //download translations
        $this->output->writeln('downloading');
        $response = $this->makeRequest(
            'GET',
            '/projects/' . $this->crowdinProjectId . '/translations/builds/' . $buildId . '/download'
        );

        if (!isset($response['data']['url'])) {
            throw new UnexpectedValueException('Missing download url');
        }

        $filesystem = new Filesystem();
        $filesystem->mkdir($this->translationsDir . '/crowdin');

        file_put_contents(
            $this->translationsDir . '/crowdin/translations.zip',
            file_get_contents($response['data']['url'])
        );

        //unzip translations
        $this->output->write('unzipping');
        $zip = new ZipArchive();
        $res = $zip->open($this->translationsDir . '/crowdin/translations.zip');

        if ($res !== true) {
            throw new UnexpectedValueException('Cannot open zip file - ' . $this->getZipError($res));
        }

        $zip->extractTo($this->translationsDir . '/crowdin');
        $zip->close();

        //copy & cleanup
        $this->output->write('.. copying');
        $finder = new Finder();
        $finder->files()->in($this->translationsDir . '/crowdin')->name('*.xlf');
        foreach ($finder as $file) {
            $this->output->write('.');
            $filesystem->copy($file->getPathname(), $this->translationsDir . '/' . $file->getFilename());
        }
        $filesystem->remove($this->translationsDir . '/crowdin');

        $this->output->writeln('finished');

        return 0;
    }

    private function downloadSourceFiles()
    {
        $this->output->writeln('Downloading source files');

        $crowdinSourceFiles = $this->makeRequest('GET', '/projects/' . $this->crowdinProjectId . '/files');

        if (!isset($crowdinSourceFiles['data'])) {
            throw new UnexpectedValueException('Unexpected Crowdin response format. Missing source files');
        }

        foreach ($crowdinSourceFiles['data'] as $file) {
            if (!isset($file['data']['id']) || !isset($file['data']['name'])) {
                throw new UnexpectedValueException('Unexpected Crowdin response format. Missing file id or name');
            }

            $fileId = $file['data']['id'];
            $fileName = $file['data']['name'];

            $this->output->writeln($fileName);

            $fileDownload = $this->makeRequest('GET', '/projects/' . $this->crowdinProjectId . '/files/' . $fileId . '/download');

            if (!isset($fileDownload['data']['url'])) {
                throw new UnexpectedValueException('Unexpected Crowdin response format. Missing file download url');
            }

            $url = $fileDownload['data']['url'];

            $response = $this->client->request('GET', $url);

            if ($response->getStatusCode() !== Response::HTTP_OK) {
                throw new RuntimeException('Cannot download source file ' . $fileName);
            }

            $content = $response->getContent();
            $res = file_put_contents($this->translationsDir . '/' . $fileName, $content);

            if ($res === false) {
                throw new UnexpectedValueException('Cannot save file ' . $fileName);
            }
        }

        $this->output->writeln('Downloading source files completed');
    }
}
