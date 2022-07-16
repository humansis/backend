<?php

namespace NewApiBundle\Utils;

/**
 * ExcelColumnsGenerator generates list of excel columns index, eg. A, B, C, ..., Y, Z, AA, AB ...
 */
class ExcelColumnsGenerator
{
    private const ALPHABET_COUNT = 26;

    private $position = 0;

    public function getNext(): string
    {
        $current = $this->generate()->current();

        $this->generate()->next();

        return $current;
    }

    public function reset()
    {
        $this->position = 0;
    }

    /**
     * @return \Generator|string[]
     */
    private function generate()
    {
        $prefix = '';

        $pos = $this->position % self::ALPHABET_COUNT;
        $it = floor($this->position / self::ALPHABET_COUNT);

        if ($it > 0) {
            $prefixChar = ord('A') + $it - 1;
            $prefix = chr($prefixChar);
        }

        $char = ord('A') + $pos;

        yield $prefix.chr($char);

        ++$this->position;
    }
}
