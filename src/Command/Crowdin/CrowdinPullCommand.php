<?php

declare(strict_types=1);

namespace Command\Crowdin;

use JsonException;
use Exception\CrowdinBuildTimeoutException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpClient\HttpClient;
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
            ->setName('crowdin:pull')
            ->setDescription('Get translations from Crowdin');
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

        file_put_contents($this->translationsDir . '/crowdin/translations.zip', file_get_contents($response['data']['url']));

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
}
