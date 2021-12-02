<?php declare(strict_types=1);

namespace Tests\NewApiBundle\Component\Storage;

use League\Flysystem\FilesystemException;
use NewApiBundle\Component\Storage\Aws\AwsStorage;
use NewApiBundle\Component\Storage\Aws\AwsStorageFactory;
use NewApiBundle\Component\Storage\StorageConfig;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class StorageAwsTest extends KernelTestCase
{
    private const
        KEY = 'AKIAYYVDSYNUDIO7EFGL',
        SECRET = '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',
        REGION = 'eu-central-1',
        VERSION = 'latest',
        BUCKET = 'logs.humansis.org',
        FOLDER = 'logs',
        FILE_NAME = 'test_file.png';

    /**
     * @var AwsStorageFactory
     */
    private static $awsStorageFactory;

    /**
     * @var StorageConfig
     */
    private static $awsConfig;

    /**
     * @var AwsStorage
     */
    private static $aws;

    /**
     * @var string
     */
    private static $filePath;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        $kernel = self::bootKernel();

        self::$awsStorageFactory = $kernel->getContainer()->get(AwsStorageFactory::class);
        self::$awsConfig = new StorageConfig(self::KEY, self::SECRET, self::REGION, self::VERSION, self::BUCKET);
        self::$aws = self::$awsStorageFactory->create(self::$awsConfig);
        self::$filePath = self::FOLDER.'/'.self::FILE_NAME;
    }

    /**
     * @throws FilesystemException
     */
    public function testUploadFile(): void
    {
        $file = file_get_contents(__DIR__.'/../../Resources/logo.png');
        $uploadedPath = self::$aws->upload(self::$filePath, $file);

        $this->assertEquals(self::$filePath, $uploadedPath, "Wrong uploaded file path");
    }

    /**
     * @depends testUploadFile
     *
     * @throws FilesystemException
     */
    public function testDeleteFile(): void
    {
        $result = self::$aws->delete(self::$filePath);

        $this->assertTrue($result);
    }
}
