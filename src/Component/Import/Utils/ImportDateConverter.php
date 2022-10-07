<?php

declare(strict_types=1);

namespace Component\Import\Utils;

use DateTime;
use DateTimeInterface;
use Negotiation\Exception\InvalidArgument;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class ImportDateConverter
{
    public const STRING_DATE_FORMAT = 'd-m-Y';

    /**
     * @param string|int|float $value
     *
     * @return DateTime
     */
    public static function toDatetime($value): DateTime
    {
        if (is_string($value)) {
            $datetime = DateTime::createFromFormat(self::STRING_DATE_FORMAT, $value);

            if (!$datetime) {
                throw new InvalidArgument(
                    "Provided value '$value' is not valid import date format. Date has to be in this format: '" . self::STRING_DATE_FORMAT . "'"
                );
            }

            return $datetime;
        }

        if (is_float($value) || is_int($value)) {
            return Date::excelToDateTimeObject($value);
        }

        throw new InvalidArgument(
            "Provided value '$value' should be of type string, float or integer. Type of provided value: " . gettype(
                $value
            )
        );
    }

    public static function toIso(?DateTimeInterface $dateTime): ?string
    {
        if (!$dateTime) {
            return null;
        }

        return $dateTime->format(DateTimeInterface::ISO8601);
    }
}
