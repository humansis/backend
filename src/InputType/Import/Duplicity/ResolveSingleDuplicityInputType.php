<?php

declare(strict_types=1);

namespace InputType\Import\Duplicity;

use Enum\ImportQueueState;
use Request\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;

class ResolveSingleDuplicityInputType implements InputTypeInterface
{
    /**
     * @var string
     *
     * @Assert\Type("string")
     * @Assert\NotNull()
     * @Assert\Choice(callback="allowedStatuses")
     */
    private $status;

    /**
     * @var int|null
     *
     * @Assert\Type("integer")
     */
    private $acceptedDuplicityId;

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

    /**
     * @return int|null
     */
    public function getAcceptedDuplicityId()
    {
        return $this->acceptedDuplicityId;
    }

    /**
     * @param int|null $acceptedDuplicityId
     */
    public function setAcceptedDuplicityId($acceptedDuplicityId)
    {
        $this->acceptedDuplicityId = $acceptedDuplicityId;
    }
}
