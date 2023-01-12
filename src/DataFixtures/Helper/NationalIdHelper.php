<?php

declare(strict_types=1);

namespace DataFixtures\Helper;

use Exception;

trait NationalIdHelper
{
    private array $numbersGenerated = [];

    /**
     * The number of digits to be used must be predicted and the range specified correctly,
     * otherwise similar numbers will be returned.
     *
     */
    public function generateRandomNumbers($min, $max): int
    {
        $random = rand($min, $max);
        $number = $random + rand($min, $max);
        $i = 0;
        while (in_array($number, $this->numbersGenerated)) {
            $number = $random + rand($min, $max);
            /*The loop will be broken when the range is smaller
             than the number of unique digits to use.*/
            $i++;
            if ($i == 10000) {
                break;
            }
        }
        $this->numbersGenerated[] = $number;
        return $number;
    }
}
