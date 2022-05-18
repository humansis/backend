<?php declare(strict_types=1);

namespace NewApiBundle\Component\Import\Workflow;

use NewApiBundle\Enum\ImportQueueState;

final class ImportQueueTransitions
{
    public const
        VALIDATE = ImportQueueState::VALID,
        INVALIDATE = ImportQueueState::INVALID,
        INVALIDATE_EXPORT = ImportQueueState::INVALID_EXPORTED,
        IDENTITY_CANDIDATE = ImportQueueState::IDENTITY_CANDIDATE,
        UNIQUE_CANDIDATE = ImportQueueState::UNIQUE_CANDIDATE,
        SIMILARITY_CANDIDATE = ImportQueueState::SIMILARITY_CANDIDATE,
        TO_CREATE = ImportQueueState::TO_CREATE,
        TO_UPDATE = ImportQueueState::TO_UPDATE,
        TO_LINK = ImportQueueState::TO_LINK,
        TO_IGNORE = ImportQueueState::TO_IGNORE,
        RESET = 'reset',
        CREATE = ImportQueueState::CREATED,
        UPDATE = ImportQueueState::UPDATED,
        LINK = ImportQueueState::LINKED;
}
