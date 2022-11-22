<?php

declare(strict_types=1);

namespace Utils\FileSystem;

use GdImage;
use Utils\FileSystem\Exception\CorruptedFileException;
use Utils\FileSystem\Exception\NotSupportedExtensionException;

class Image
{
    public const JPEG = 'jpeg';
    public const JPG = 'jpg';
    public const PNG = 'png';
    public const GIF = 'gif';

    /**
     * @return string[]
     */
    public static function getSupportedImageExtensions(): array
    {
        return [
            self::JPG,
            self::JPEG,
            self::PNG,
            self::GIF,
        ];
    }

    /**
     * @param string $filePath
     *
     * @return GdImage|resource
     * @throws NotSupportedExtensionException|CorruptedFileException
     */
    public static function getImageResource(string $filePath)
    {
        $type = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        switch ($type) {
            case self::GIF:
                $image = imagecreatefromgif($filePath);
                break;
            case self::JPEG:
            case self::JPG:
                $image = imagecreatefromjpeg($filePath);
                break;
            case self::PNG:
                $image = imagecreatefrompng($filePath);
                break;
            default:
                throw new NotSupportedExtensionException(
                    sprintf(
                        'Unsupported type %s. Supported types are (%s)',
                        $type,
                        implode(self::getSupportedImageExtensions())
                    )
                );
        }

        if ($image) {
            return $image;
        } else {
            throw new CorruptedFileException("Cannot get image resource. File $filePath is probably corrupted.");
        }
    }
}
