<?php

declare(strict_types=1);

namespace Enum;

final class ImportQueueState
{
    use EnumTrait;

    public const NEW = 'New';
    public const VALID = 'Valid';
    public const INVALID = 'Invalid';
    public const INVALID_EXPORTED = 'Invalid Exported';
    public const IDENTITY_CANDIDATE = 'Identity Candidate';
    public const UNIQUE_CANDIDATE = 'Unique Candidate';
    public const SIMILARITY_CANDIDATE = 'Similarity Candidate';
    public const TO_CREATE = 'To Create';
    public const TO_UPDATE = 'To Update';
    public const TO_LINK = 'To Link';
    public const TO_IGNORE = 'To Ignore';
    public const CREATED = 'Created';
    public const UPDATED = 'Updated';
    public const LINKED = 'Linked';
    public const IGNORED = 'Ignored';
    public const ERROR = 'Error';

    public static function values(): array
    {
        return [
            self::NEW,
            self::VALID,
            self::INVALID,
            self::INVALID_EXPORTED,
            self::IDENTITY_CANDIDATE,
            self::UNIQUE_CANDIDATE,
            self::SIMILARITY_CANDIDATE,
            self::TO_CREATE,
            self::TO_UPDATE,
            self::TO_LINK,
            self::TO_IGNORE,
            self::CREATED,
            self::UPDATED,
            self::LINKED,
            self::IGNORED,
            self::ERROR,
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

    public static function importedStates(): array
    {
        return [
            self::CREATED,
            self::UPDATED,
            self::LINKED,
            self::IGNORED,
        ];
    }
}
