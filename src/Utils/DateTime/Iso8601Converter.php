<?php

declare(strict_types=1);

namespace Utils\DateTime;

use DateTime;
use DateTimeInterface;

final class Iso8601Converter
{
    public static function toDateTime(string $dateTimeString): ?DateTimeInterface
    {
        foreach ([DateTimeInterface::ISO8601, DateTimeInterface::ATOM, 'Y-m-d\TH:i:s.u\Z', 'Y-m-d'] as $format) {
            $dateTime = DateTime::createFromFormat($format, $dateTimeString);

            if (false !== $dateTime) {
                break;
            }
        }

        return $dateTime ?: null;
    }
}
