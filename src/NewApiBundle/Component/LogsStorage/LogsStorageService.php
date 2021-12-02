<?php declare(strict_types=1);

namespace NewApiBundle\Component\LogsStorage;

use League\Flysystem\FilesystemException;
use NewApiBundle\Component\Storage\Aws\AwsStorageFactory;
use NewApiBundle\Component\Storage\StorageConfig;

class LogsStorageService
{
    /**
     * @var string
     */
    private $key;

    /**
     * @var string
     */
    private $secret;

    /**
     * @var string
     */
    private $region;

    /**
     * @var string
     */
    private $version;

    /**
     * @var string
     */
    private $bucketName;

    /**
     * @var string
     */
    private $folder;

    /**
     * @var StorageConfig
     */
    private $awsConfig;

    /**
     * @var AwsStorageFactory
     */
    private $awsStorageFactory;

    public function __construct(
        string            $key,
        string            $secret,
        string            $region,
        string            $version,
        string            $bucketName,
        string            $folder,
        AwsStorageFactory $awsStorageFactory
    ) {
        $this->key = $key;
        $this->secret = $secret;
        $this->region = $region;
        $this->version = $version;
        $this->bucketName = $bucketName;
        $this->folder = $folder;
        $this->awsConfig = new StorageConfig($this->key, $this->secret, $this->region, $this->version, $this->bucketName);
        $this->awsStorageFactory = $awsStorageFactory;
    }

    /**
     * @param string $fileName
     * @param        $file
     *
     * @return string
     * @throws FilesystemException
     */
    private function upload(string $fileName, $file): string
    {
        $path = $this->folder.'/'.$fileName;
        $aws = $this->awsStorageFactory->create($this->awsConfig);

        return $aws->upload($path, $file);
    }
}
