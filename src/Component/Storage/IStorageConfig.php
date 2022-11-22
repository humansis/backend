<?php

declare(strict_types=1);

namespace Component\Storage;

interface IStorageConfig
{
    public function getOptions(): array;

    public function getBucketName(): string;
}
