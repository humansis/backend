<?php

declare(strict_types=1);

namespace Component\Storage;

class StorageConfig implements IStorageConfig
{
    public function __construct(private string $key, private string $secret, private string $region, private string $version, private string $bucketName)
    {
    }

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

    public function getBucketName(): string
    {
        return $this->bucketName;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function setKey(string $key): void
    {
        $this->key = $key;
    }

    public function getSecret(): string
    {
        return $this->secret;
    }

    public function setSecret(string $secret): void
    {
        $this->secret = $secret;
    }

    public function getRegion(): string
    {
        return $this->region;
    }

    public function setRegion(string $region): void
    {
        $this->region = $region;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function setVersion(string $version): void
    {
        $this->version = $version;
    }

    public function setBucketName(string $bucketName): void
    {
        $this->bucketName = $bucketName;
    }
}
