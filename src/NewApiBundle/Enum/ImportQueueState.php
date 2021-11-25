<?php
declare(strict_types=1);

namespace NewApiBundle\Enum;

final class ImportQueueState
{
    const NEW = 'New';
    const VALID = 'Valid';
    const INVALID = 'Invalid';
    const INVALID_EXPORTED = 'Invalid Exported';
    const IDENTITY_CANDIDATE = 'Identity Candidate';
    const UNIQUE_CANDIDATE = 'Unique Candidate';
    const SIMILARITY_CANDIDATE = 'Similarity Candidate';
    const SUSPICIOUS = 'Suspicious';
    const TO_CREATE = 'To Create';
    const TO_UPDATE = 'To Update';
    const TO_LINK = 'To Link';
    const TO_IGNORE = 'To Ignore';
    const CREATED = 'Created';
    const UPDATED = 'Updated';
    const LINKED = 'Linked';

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
