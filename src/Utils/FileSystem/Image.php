<?php

declare(strict_types=1);

namespace Utils\FileSystem;

use GdImage;
use Utils\FileSystem\Exception\CorruptedFileException;
use Utils\FileSystem\Exception\NotSupportedExtensionException;

class Image
{
    final public const JPEG = 'jpeg';
    final public const JPG = 'jpg';
    final public const PNG = 'png';
    final public const GIF = 'gif';

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
     *
     * @return GdImage|resource
     * @throws NotSupportedExtensionException|CorruptedFileException
     */
    public static function getImageResource(string $filePath)
    {
        $type = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $image = match ($type) {
            self::GIF => imagecreatefromgif($filePath),
            self::JPEG, self::JPG => imagecreatefromjpeg($filePath),
            self::PNG => imagecreatefrompng($filePath),
            default => throw new NotSupportedExtensionException(
                sprintf(
                    'Unsupported type %s. Supported types are (%s)',
                    $type,
                    implode(self::getSupportedImageExtensions())
                )
            ),
        };

        if ($image) {
            return $image;
        } else {
            throw new CorruptedFileException("Cannot get image resource. File $filePath is probably corrupted.");
        }
    }
}
