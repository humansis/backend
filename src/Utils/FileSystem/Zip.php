<?php

declare(strict_types=1);

namespace Utils\FileSystem;

use RuntimeException;
use Symfony\Component\HttpFoundation\File\File;
use ZipArchive;

final class Zip
{
    private const TEMP_DIR_CREATION_ATTEMPTS = 1000;

    /**
     * @return string
     *
     * @throws RuntimeException
     */
    private static function createTempDir(): string
    {
        for ($i = 0; $i < self::TEMP_DIR_CREATION_ATTEMPTS; $i++) {
            $path = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . mt_rand() . time();
            if (mkdir($path)) {
                return $path;
            }
        }

        throw new RuntimeException('Unable to create temp directory. Maximum attempts reached.');
    }

    /**
     * @param File $file
     *
     * @return array of extracted files in format:
     *      original path in archive => absolute path to extracted file on disc
     */
    public static function extractToTempDir(File $file): array
    {
        $zipArchive = new ZipArchive();

        if (!$zipArchive->open($file->getRealPath())) {
            throw new RuntimeException("File {$file->getRealPath()} could not be open as archive.");
        }

        $tempDir = self::createTempDir();
        $zipArchive->extractTo($tempDir);

        $files = [];

        for ($i = 0; $i < $zipArchive->numFiles; $i++) {
            if ($zipArchive->statIndex($i)['size'] > 0) { //this condition filters folders
                $files[$zipArchive->getNameIndex($i)] = $tempDir . '/' . $zipArchive->getNameIndex($i);
            }
        }

        return $files;
    }
}
