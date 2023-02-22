<?php

declare(strict_types=1);

namespace Utils\DateTime;

use DateTime;
use DateTimeInterface;

final class Iso8601Converter
{
    public static function toDateTime(string $dateTimeString): ?DateTimeInterface
    {
        foreach ([DateTimeInterface::ATOM, DateTimeFormat::DATETIME_WITH_TIMEZONE, DateTimeFormat::DATE] as $format) {
            $dateTime = DateTime::createFromFormat($format, $dateTimeString);

            if (false !== $dateTime) {
                break;
            }
        }

        return $dateTime ?: null;
    }
}
