<?php

declare(strict_types=1);

namespace Validator\Constraints;

use DateTime;
use DateTimeInterface;
use Exception;
use Symfony\Component\Validator\Constraints\GreaterThanValidator;

class DateGreaterThanValidator extends GreaterThanValidator
{
    /**
     * {@inheritdoc}
     */
    protected function compareValues($value1, $value2)
    {
        try {
            if (is_string($value1)) {
                $value1 = new DateTime($value1);
            }
            if (is_string($value2)) {
                $value2 = new DateTime($value2);
            }
        } catch (Exception $ex) {
            return false;
        }

        if (!$value1 instanceof DateTimeInterface || !$value2 instanceof DateTimeInterface) {
            return false;
        }

        return $value1 > $value2;
    }

    /**
     * {@inheritdoc}
     */
    protected function getErrorCode()
    {
        return DateGreaterThan::TOO_LOW_ERROR;
    }
}
