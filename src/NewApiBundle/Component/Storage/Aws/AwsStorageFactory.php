<?php declare(strict_types=1);

namespace NewApiBundle\Component\Storage\Aws;


use NewApiBundle\Component\Storage\IStorageConfig;

class AwsStorageFactory
{
    /**
     * @param IStorageConfig $storageConfig
     *
     * @return AwsStorage
     */
    public function create(IStorageConfig $storageConfig): AwsStorage
    {
        return new AwsStorage($storageConfig);
    }
}
