<?php
declare(strict_types=1);

namespace NewApiBundle\InputType;

use NewApiBundle\Request\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;

class DuplicityResolveInputType implements InputTypeInterface
{
    /**
     * @var string
     *
     * @Assert\Type("string")
     * @Assert\NotNull()
     * @Assert\Choice(callback={"NewApiBundle\Enum\ImportQueueState", "values"})
     */
    private $status;

    /**
     * @var integer
     *
     * @Assert\Type("integer")
     * @Assert\NotNull()
     */
    private $acceptedDuplicityId;

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
     * @return int
     */
    public function getAcceptedDuplicityId()
    {
        return $this->acceptedDuplicityId;
    }

    /**
     * @param int $acceptedDuplicityId
     */
    public function setAcceptedDuplicityId($acceptedDuplicityId)
    {
        $this->acceptedDuplicityId = $acceptedDuplicityId;
    }
}
