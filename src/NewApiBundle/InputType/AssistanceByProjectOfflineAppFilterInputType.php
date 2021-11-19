<?php
declare(strict_types=1);

namespace NewApiBundle\InputType;

use NewApiBundle\InputType\FilterFragment\ModalityTypeFilterTrait;
use NewApiBundle\Request\FilterInputType\AbstractFilterInputType;
use Symfony\Component\Validator\Constraints as Assert;

class AssistanceByProjectOfflineAppFilterInputType extends AbstractFilterInputType
{
    use ModalityTypeFilterTrait;

    /**
     * @Assert\Choice(callback={"DistributionBundle\Enum\AssistanceType", "values"})
     */
    protected $type;

    /**
     * @Assert\Choice({0, 1}, message="Invalid boolean value. Accepted are 0,1, given {{ value }}.")
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
    protected $notModalityTypes;

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
        return (bool) $this->completed;
    }

    public function hasNotModalityTypes(): bool
    {
        return $this->has('notModalityTypes');
    }

    public function getNotModalityTypes(): array
    {
        return $this->notModalityTypes;
    }
}
