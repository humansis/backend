<?php declare(strict_types=1);

namespace Factory;


use Component\Storage\Aws\AwsStorage;
use Component\Storage\IStorageConfig;

class AwsStorageFactory implements Factory
{
    /**
     * @param IStorageConfig|null $storageConfig
     *
     * @return AwsStorage
     */
    public function create(IStorageConfig $storageConfig = null): object
    {
        return new AwsStorage($storageConfig);
    }
}
