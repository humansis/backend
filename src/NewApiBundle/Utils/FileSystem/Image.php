<?php declare(strict_types=1);

namespace NewApiBundle\Utils\FileSystem;

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
     * @return false|\GdImage|resource
     */
    public static function getImageResource(string $filePath)
    {
        $type = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        switch ($type) {
            case self::GIF:
                return imagecreatefromgif($filePath);
            case self::JPEG:
            case self::JPG:
                return imagecreatefromjpeg($filePath);
            case self::PNG:
                return imagecreatefrompng($filePath);
            default:
                throw new \LogicException(sprintf('Unsupported type %s. Supported types are (%s)', $type,
                    implode(self::getSupportedImageExtensions())));
        }
    }
}
