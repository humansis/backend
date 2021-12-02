<?php declare(strict_types=1);

namespace NewApiBundle\Component\Storage;

class StorageConfig implements IStorageConfig
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

    public function __construct(string $key, string $secret, string $region, string $version, string $bucketName)
    {
        $this->key = $key;
        $this->secret = $secret;
        $this->region = $region;
        $this->version = $version;
        $this->bucketName = $bucketName;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return [
            StorageEnum::CREDENTIALS => [
                StorageEnum::KEY => $this->getKey(),
                StorageEnum::SECRET => $this->getSecret(),
            ],
            StorageEnum::REGION => $this->getRegion(),
            StorageEnum::VERSION => $this->getVersion(),
        ];
    }

    /**
     * @return string
     */
    public function getBucketName(): string
    {
        return $this->bucketName;
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @param string $key
     */
    public function setKey(string $key): void
    {
        $this->key = $key;
    }

    /**
     * @return string
     */
    public function getSecret(): string
    {
        return $this->secret;
    }

    /**
     * @param string $secret
     */
    public function setSecret(string $secret): void
    {
        $this->secret = $secret;
    }

    /**
     * @return string
     */
    public function getRegion(): string
    {
        return $this->region;
    }

    /**
     * @param string $region
     */
    public function setRegion(string $region): void
    {
        $this->region = $region;
    }

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * @param string $version
     */
    public function setVersion(string $version): void
    {
        $this->version = $version;
    }

    /**
     * @param string $bucketName
     */
    public function setBucketName(string $bucketName): void
    {
        $this->bucketName = $bucketName;
    }
}
