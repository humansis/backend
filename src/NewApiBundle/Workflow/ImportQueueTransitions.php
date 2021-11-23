<?php declare(strict_types=1);

namespace NewApiBundle\Workflow;

use NewApiBundle\Enum\ImportQueueState;

final class ImportQueueTransitions
{
    public const
        VALIDATE = ImportQueueState::VALID,
        INVALIDATE = ImportQueueState::INVALID,
        INVALIDATE_EXPORT = ImportQueueState::INVALID_EXPORTED,
        SUSPICIOUS = ImportQueueState::SUSPICIOUS,
        TO_CREATE = ImportQueueState::TO_CREATE,
        TO_UPDATE = ImportQueueState::TO_UPDATE,
        TO_LINK = ImportQueueState::TO_LINK,
        TO_IGNORE = ImportQueueState::TO_IGNORE,
        RESET = 'reset';
}
