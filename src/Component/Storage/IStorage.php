<?php

declare(strict_types=1);

namespace Component\Storage;

interface IStorage
{
    /**
     * @param string $path
     * @param        $file
     * @param string $visibility
     *
     * @return string
     */
    public function upload(string $path, $file, string $visibility = StorageEnum::PRIVATE_S): string;

    /**
     * @param string $filePath
     *
     * @return bool
     */
    public function delete(string $filePath): bool;
}
