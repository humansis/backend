<?php
declare(strict_types=1);

namespace NewApiBundle\Enum;

final class ImportState
{
    const NEW = 'New';
    const INTEGRITY_CHECKING = 'Integrity Checking';
    const INTEGRITY_CHECK_CORRECT = 'Integrity Check Correct';
    const INTEGRITY_CHECK_FAILED = 'Integrity Check Failed';
    const IDENTITY_CHECKING = 'Identity Checking';
    const IDENTITY_CHECK_CORRECT = 'Identity Check Correct';
    const IDENTITY_CHECK_FAILED = 'Identity Check Failed';
    const SIMILARITY_CHECKING = 'Similarity Checking';
    const SIMILARITY_CHECK_CORRECT = 'Similarity Check Correct';
    const SIMILARITY_CHECK_FAILED = 'Similarity Check Failed';
    const IMPORTING = 'Importing';
    const FINISHED = 'Finished';
    const CANCELED = 'Canceled';

    public static function values(): array
    {
        return [
            self::NEW,
            self::INTEGRITY_CHECKING,
            self::INTEGRITY_CHECK_CORRECT,
            self::INTEGRITY_CHECK_FAILED,
            self::IDENTITY_CHECKING,
            self::IDENTITY_CHECK_CORRECT,
            self::IDENTITY_CHECK_FAILED,
            self::SIMILARITY_CHECKING,
            self::SIMILARITY_CHECK_CORRECT,
            self::SIMILARITY_CHECK_FAILED,
            self::IMPORTING,
            self::FINISHED,
            self::CANCELED,
        ];
    }
}
