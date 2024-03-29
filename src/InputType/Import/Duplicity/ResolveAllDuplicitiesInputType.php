<?php

declare(strict_types=1);

namespace InputType\Import\Duplicity;

use Enum\ImportQueueState;
use Request\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;

class ResolveAllDuplicitiesInputType implements InputTypeInterface
{
    #[Assert\Type('string')]
    #[Assert\NotNull]
    #[Assert\Choice(callback: 'allowedStatuses')]
    private ?string $status = null;

    public static function allowedStatuses(): array
    {
        return [
            ImportQueueState::TO_CREATE,
            ImportQueueState::TO_UPDATE,
            ImportQueueState::TO_LINK,
            ImportQueueState::TO_IGNORE,
        ];
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }
}
