<?php
declare(strict_types=1);

namespace NewApiBundle\Validator\Constraints;

class ImportDateValidator extends \Symfony\Component\Validator\Constraints\DateValidator
{
    public const PATTERN = '/^(\d{2})-(\d{2})-(\d{4})$/';

    /**
     * @inheritDoc
     *
     * FYI: Changed order of parameters
     */
    public static function checkDate(int $day, int $month, int $year): bool
    {
        return checkdate($month, $day, $year);
    }
}
