<?php

declare(strict_types=1);

namespace Enum;

final class ImportDuplicityState
{
    use EnumTrait;

    public const DUPLICITY_CANDIDATE = 'Duplicity Candidate';
    public const DUPLICITY_KEEP_OURS = 'Duplicity Keep Ours';
    public const DUPLICITY_KEEP_THEIRS = 'Duplicity Keep Theirs';
    public const NO_DUPLICITY = 'No Duplicity';

    public static function values(): array
    {
        return [
            self::DUPLICITY_CANDIDATE,
            self::DUPLICITY_KEEP_OURS,
            self::DUPLICITY_KEEP_THEIRS,
            self::NO_DUPLICITY,
        ];
    }
}
