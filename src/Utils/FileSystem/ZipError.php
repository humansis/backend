<?php

namespace Utils\FileSystem;

use ZipArchive;

trait ZipError
{
    private function getZipError($res): string
    {
        switch ($res) {
            case ZipArchive::ER_EXISTS:
                return 'File already exists.';
            case ZipArchive::ER_INCONS:
                return 'Zip archive inconsistent.';
            case ZipArchive::ER_INVAL:
                return 'Invalid argument.';
            case ZipArchive::ER_MEMORY:
                return 'Malloc failure.';
            case ZipArchive::ER_NOENT:
                return 'No such file.';
            case ZipArchive::ER_NOZIP:
                return 'Not a zip archive.';
            case ZipArchive::ER_OPEN:
                return 'Can\'t open file.';
            case ZipArchive::ER_READ:
                return 'Read error.';
            case ZipArchive::ER_SEEK:
                return 'Seek error.';
        }

        return 'error code '.$res;
    }
}