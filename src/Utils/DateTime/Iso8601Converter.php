<?php

declare(strict_types=1);

namespace Utils\DateTime;

use DateTime;
use DateTimeInterface;

final class Iso8601Converter
{
    public static function toDateTime(string $dateTimeString, bool $setMidnight = false): ?DateTimeInterface
    {
        foreach ([DateTimeInterface::ATOM, DateTimeFormat::DATETIME_WITH_TIMEZONE, DateTimeFormat::DATE] as $format) {
            /** @var DateTime|null $dateTime */
            $dateTime = DateTime::createFromFormat($format, $dateTimeString);

            if (false !== $dateTime) {
                if ($setMidnight) {
                    $dateTime->setTime(0, 0);
                }

                break;
            }
        }

        return $dateTime ?: null;
    }
}
