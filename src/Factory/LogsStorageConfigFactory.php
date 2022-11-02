<?php

declare(strict_types=1);

namespace Factory;

use Component\Storage\StorageConfig;

final class LogsStorageConfigFactory implements Factory
{
    public function __construct(private readonly string $key, private readonly string $secret, private readonly string $region, private readonly string $version, private readonly string $bucketName)
    {
    }

    /**
     * @return StorageConfig
     */
    public function create(): object
    {
        return new StorageConfig($this->key, $this->secret, $this->region, $this->version, $this->bucketName);
    }
}
