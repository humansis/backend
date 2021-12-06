<?php declare(strict_types=1);

namespace NewApiBundle\Component\Import\Enum;

use NewApiBundle\Component\Import\Enum\State;

final class Transitions
{
    public const
        CHECK_INTEGRITY = State::INTEGRITY_CHECKING,
        COMPLETE_INTEGRITY = State::INTEGRITY_CHECK_CORRECT,
        FAIL_INTEGRITY = State::INTEGRITY_CHECK_FAILED,
        REDO_INTEGRITY = 'redo_integrity',

        CHECK_IDENTITY = State::IDENTITY_CHECKING,
        COMPLETE_IDENTITY = State::IDENTITY_CHECK_CORRECT,
        FAIL_IDENTITY = State::IDENTITY_CHECK_FAILED,
        REDO_IDENTITY = 'redo_identity',
        RESOLVE_IDENTITY_DUPLICITIES = 'resolve_identity_duplicities',

        CHECK_SIMILARITY = State::SIMILARITY_CHECKING,
        COMPLETE_SIMILARITY = State::SIMILARITY_CHECK_CORRECT,
        FAIL_SIMILARITY = State::SIMILARITY_CHECK_FAILED,
        REDO_SIMILARITY = 'redo_similarity',
        RESOLVE_SIMILARITY_DUPLICITIES = 'resolve_similarity_duplicities',

        IMPORT = State::IMPORTING,
        FINISH = State::FINISHED,
        CANCEL = State::CANCELED,
        RESET = 'reset';
}
