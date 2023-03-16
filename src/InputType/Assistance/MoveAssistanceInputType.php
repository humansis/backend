<?php

declare(strict_types=1);

namespace InputType\Assistance;

use Happyr\Validator\Constraint\EntityExist;
use Request\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;

class MoveAssistanceInputType implements InputTypeInterface
{
    /**
     * @var int | null
     * @EntityExist(entity="Entity\Project")
     */
    #[Assert\Type('integer')]
    #[Assert\GreaterThanOrEqual(0)]
    #[Assert\NotBlank]
    private $originalProjectId = null;

    /**
     * @var int | null
     * @EntityExist(entity="Entity\Project")
     */
    #[Assert\Type('integer')]
    #[Assert\GreaterThanOrEqual(0)]
    #[Assert\NotBlank]
    private $targetProjectId = null;

    public function setOriginalProjectId($originalProjectId): void
    {
        $this->originalProjectId = $originalProjectId;
    }

    public function setTargetProjectId($targetProjectId): void
    {
        $this->targetProjectId = $targetProjectId;
    }

    public function getOriginalProjectId()
    {
        return $this->originalProjectId;
    }

    public function getTargetProjectId()
    {
        return $this->targetProjectId;
    }
}
