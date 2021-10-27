<?php declare(strict_types=1);

namespace NewApiBundle\Workflow;

use NewApiBundle\Enum\ImportState;

final class ImportTransitions
{
    public const
        CHECK_INTEGRITY = ImportState::INTEGRITY_CHECKING,
        COMPLETE_INTEGRITY = ImportState::INTEGRITY_CHECK_CORRECT,
        FAIL_INTEGRITY = ImportState::INTEGRITY_CHECK_FAILED,
        CHECK_IDENTITY = ImportState::IDENTITY_CHECKING,
        COMPLETE_IDENTITY = ImportState::IDENTITY_CHECK_CORRECT,
        FAIL_IDENTITY = ImportState::IDENTITY_CHECK_FAILED,
        CHECK_SIMILARITY = ImportState::SIMILARITY_CHECKING,
        COMPLETE_SIMILARITY = ImportState::SIMILARITY_CHECK_CORRECT,
        FAIL_SIMILARITY = ImportState::SIMILARITY_CHECK_FAILED,
        IMPORT = ImportState::IMPORTING,
        FINISH = ImportState::FINISHED,
        CANCEL = ImportState::CANCELED;
}
