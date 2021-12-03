<?php declare(strict_types=1);

namespace NewApiBundle\Component\LogsStorage;

use NewApiBundle\Component\Storage\StorageConfig;

final class LogsStorageConfigFactory
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

    public function __construct(
        string $key,
        string $secret,
        string $region,
        string $version,
        string $bucketName
    ) {

        $this->key = $key;
        $this->secret = $secret;
        $this->region = $region;
        $this->version = $version;
        $this->bucketName = $bucketName;
    }

    /**
     * @return StorageConfig
     */
    public function create(): StorageConfig
    {
        return new StorageConfig($this->key, $this->secret, $this->region, $this->version, $this->bucketName);
    }
}
