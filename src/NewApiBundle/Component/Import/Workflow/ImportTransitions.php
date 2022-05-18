<?php declare(strict_types=1);

namespace NewApiBundle\Component\Import\Workflow;

use NewApiBundle\Enum\ImportState;

final class ImportTransitions
{
    public const
        CHECK_INTEGRITY = ImportState::INTEGRITY_CHECKING,
        COMPLETE_INTEGRITY = ImportState::INTEGRITY_CHECK_CORRECT,
        FAIL_INTEGRITY = ImportState::INTEGRITY_CHECK_FAILED,
        REDO_INTEGRITY = 'redo_integrity',

        CHECK_IDENTITY = ImportState::IDENTITY_CHECKING,
        COMPLETE_IDENTITY = ImportState::IDENTITY_CHECK_CORRECT,
        FAIL_IDENTITY = ImportState::IDENTITY_CHECK_FAILED,
        REDO_IDENTITY = 'redo_identity',
        RESOLVE_IDENTITY_DUPLICITIES = 'resolve_identity_duplicities',

        CHECK_SIMILARITY = ImportState::SIMILARITY_CHECKING,
        COMPLETE_SIMILARITY = ImportState::SIMILARITY_CHECK_CORRECT,
        FAIL_SIMILARITY = ImportState::SIMILARITY_CHECK_FAILED,
        REDO_SIMILARITY = 'redo_similarity',
        RESOLVE_SIMILARITY_DUPLICITIES = 'resolve_similarity_duplicities',

        IMPORT = ImportState::IMPORTING,
        FINISH = ImportState::FINISHED,
        CANCEL = ImportState::CANCELED,
        RESET = 'reset';
}
