<?php

declare(strict_types=1);

namespace Tests\Component\Storage;

use League\Flysystem\FilesystemException;
use Component\Storage\Aws\AwsStorage;
use Component\Storage\StorageConfig;
use Factory\AwsStorageFactory;
use Factory\LogsStorageConfigFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class StorageAwsTest extends KernelTestCase
{
    private const
        FOLDER = 'testing',
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

    private static string $filePath;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $kernel = self::bootKernel();

        self::$awsStorageFactory = $kernel->getContainer()->get(AwsStorageFactory::class);
        self::$awsConfig = ($kernel->getContainer()->get(LogsStorageConfigFactory::class))->create();
        self::$aws = self::$awsStorageFactory->create(self::$awsConfig);
        self::$filePath = self::FOLDER . '/' . self::FILE_NAME;
    }

    /**
     * @throws FilesystemException
     */
    public function testUploadFile(): never
    {
        $this->markTestSkipped('not on local');

        $file = file_get_contents(__DIR__ . '/../../Resources/logo.png');
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
