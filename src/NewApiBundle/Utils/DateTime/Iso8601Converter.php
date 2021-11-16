<?php
declare(strict_types=1);

namespace NewApiBundle\Utils\DateTime;

use DateTimeInterface;

final class Iso8601Converter
{
    /**
     * @param string $dateTimeString
     *
     * @return DateTimeInterface|null
     */
    public static function toDateTime(string $dateTimeString): ?DateTimeInterface
    {
        foreach ([\DateTimeInterface::ISO8601, \DateTimeInterface::ATOM, 'Y-m-d\TH:i:s.u\Z', 'Y-m-d'] as $format) {
            $dateTime = \DateTime::createFromFormat($format, $dateTimeString);

            if (false !== $dateTime) {
                break;
            }
        }

        return $dateTime;
    }
}
