<?php
declare(strict_types=1);

namespace NewApiBundle\Enum;

final class ImportDuplicityState
{
    const DUPLICITY_CANDIDATE = 'Duplicity Candidate';
    const DUPLICITY_KEEP_OURS = 'Duplicity Keep Ours';
    const DUPLICITY_KEEP_THEIRS = 'Duplicity Keep Theirs';
    const NO_DUPLICITY = 'No Duplicity';

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
