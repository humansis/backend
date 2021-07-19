<?php
declare(strict_types=1);

namespace NewApiBundle\InputType;

use NewApiBundle\Request\FilterInputType\AbstractFilterInputType;
use Symfony\Component\Validator\Constraints as Assert;

class AssistanceByProjectOfflineAppFilterInputType extends AbstractFilterInputType
{
    /**
     * @Assert\Choice(callback={"DistributionBundle\Enum\AssistanceType", "values"})
     */
    protected $type;

    /**
     * @Assert\Type("boolean")
     */
    protected $completed;

    /**
     * @Assert\Type("array")
     * @Assert\All(
     *     constraints={
     *         @Assert\Type("string", groups={"Strict"})
     *     },
     *     groups={"Strict"}
     * )
     */
    protected $modalityTypes;

    public function hasType(): bool
    {
        return $this->has('type');
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function hasCompleted(): bool
    {
        return $this->has('completed');
    }

    public function getCompleted(): bool
    {
        return $this->completed;
    }

    public function hasModalityTypes(): bool
    {
        return $this->has('modalityTypes');
    }

    public function getModalityTypes(): array
    {
        return $this->modalityTypes;
    }
}
