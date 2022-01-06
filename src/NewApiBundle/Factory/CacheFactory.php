<?php declare(strict_types=1);

namespace NewApiBundle\Factory;

use Symfony\Component\Cache\Adapter\FilesystemAdapter;

class CacheFactory
{
    public function create(): FilesystemAdapter
    {
        return new FilesystemAdapter();
    }
}
