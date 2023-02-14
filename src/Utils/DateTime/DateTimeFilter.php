<?php

declare(strict_types=1);

namespace Utils\DateTime;

use DateTimeInterface;

final class DateTimeFilter
{
    public static function getDateTimeFromFilterDate(string $filterDate, bool $addDay = false): DateTimeInterface
    {
        $dateTime = Iso8601Converter::toDateTime($filterDate);
        if ($addDay) {
            $dateTime->modify('+1 day');
        }

        return $dateTime;
    }
}
