<?php declare(strict_types=1);

namespace NewApiBundle\Component\Storage;

interface IStorageConfig
{
    public function getOptions(): array;

    public function getBucketName(): string;
}
