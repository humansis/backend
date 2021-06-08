<?php
declare(strict_types=1);

namespace NewApiBundle\Enum;

final class ImportQueueState
{
    const NEW = 'New';
    const VALID = 'Valid';
    const INVALID = 'Invalid';
    const INVALID_EXPORTED = 'Invalid Exported';
    const SUSPICIOUS = 'Suspicious';
    const TO_CREATE = 'To Create';
    const TO_UPDATE = 'To Update';
    const TO_LINK = 'To Link';
    const TO_IGNORE = 'To Ignore';

    public static function values(): array
    {
        return [
            self::NEW,
            self::VALID,
            self::INVALID,
            self::INVALID_EXPORTED,
            self::SUSPICIOUS,
            self::TO_CREATE,
            self::TO_UPDATE,
            self::TO_LINK,
            self::TO_IGNORE,
        ];
    }

    public static function readyToImportStates(): array
    {
        return [
            self::TO_CREATE,
            self::TO_UPDATE,
            self::TO_LINK,
            self::TO_IGNORE,
        ];
    }
}
