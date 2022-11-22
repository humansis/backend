<?php

declare(strict_types=1);

namespace Enum;

final class ImportState
{
    use EnumTrait;

    public const NEW = 'New';
    public const UPLOADING = 'Uploading';
    public const UPLOAD_FAILED = 'Upload Failed';
    public const INTEGRITY_CHECKING = 'Integrity Checking';
    public const INTEGRITY_CHECK_CORRECT = 'Integrity Check Correct';
    public const INTEGRITY_CHECK_FAILED = 'Integrity Check Failed';
    public const IDENTITY_CHECKING = 'Identity Checking';
    public const IDENTITY_CHECK_CORRECT = 'Identity Check Correct';
    public const IDENTITY_CHECK_FAILED = 'Identity Check Failed';
    public const SIMILARITY_CHECKING = 'Similarity Checking';
    public const SIMILARITY_CHECK_CORRECT = 'Similarity Check Correct';
    public const SIMILARITY_CHECK_FAILED = 'Similarity Check Failed';
    public const IMPORTING = 'Importing';
    public const FINISHED = 'Finished';
    public const CANCELED = 'Canceled';

    public static function values(): array
    {
        return [
            self::NEW,
            self::UPLOADING,
            self::UPLOAD_FAILED,
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
