<?php declare(strict_types=1);

namespace NewApiBundle\Component\Import\Enum;

use NewApiBundle\Component\Import\Enum\QueueState;

final class QueueTransitions
{
    public const
        VALIDATE = QueueState::VALID,
        INVALIDATE = QueueState::INVALID,
        INVALIDATE_EXPORT = QueueState::INVALID_EXPORTED,
        IDENTITY_CANDIDATE = QueueState::IDENTITY_CANDIDATE,
        UNIQUE_CANDIDATE = QueueState::UNIQUE_CANDIDATE,
        SIMILARITY_CANDIDATE = QueueState::SIMILARITY_CANDIDATE,
        TO_CREATE = QueueState::TO_CREATE,
        TO_UPDATE = QueueState::TO_UPDATE,
        TO_LINK = QueueState::TO_LINK,
        TO_IGNORE = QueueState::TO_IGNORE,
        RESET = 'reset',
        CREATE = QueueState::CREATED,
        UPDATE = QueueState::UPDATED,
        LINK = QueueState::LINKED;
}
