<?php

declare(strict_types=1);

namespace Utils\FileSystem;

use InvalidArgumentException;

final class PathConstructor
{
    /**
     * @param string $pathTemplate
     * @param array $parameters
     *
     * @return string
     */
    public static function construct(string $pathTemplate, array $parameters): string
    {
        $path = $pathTemplate;

        foreach ($parameters as $key => $value) {
            $paramPlaceholder = '<<' . $key . '>>';
            $path = str_replace($paramPlaceholder, $value, $path);
        }

        $matches = [];

        preg_match_all('/<<(.*?)>>/', $path, $matches);

        if (!empty($matches[1])) {
            throw new InvalidArgumentException('Not all parameters for path were provided. Missing parameters: [' . join($matches[1], ' , ') . ']');
        }

        return $path;
    }
}
