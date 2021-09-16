<?php
declare(strict_types=1);

namespace NewApiBundle\InputType;

use NewApiBundle\InputType\FilterFragment\LocationFilterTrait;
use NewApiBundle\InputType\FilterFragment\PrimaryIdFilterTrait;
use NewApiBundle\InputType\FilterFragment\ProjectFilterTrait;
use NewApiBundle\Request\FilterInputType\AbstractFilterInputType;
use Symfony\Component\Validator\Constraints as Assert;

class AssistanceFilterInputType extends AbstractFilterInputType
{
    use PrimaryIdFilterTrait;
    use ProjectFilterTrait;
    use LocationFilterTrait;

    /**
     * @Assert\Type("boolean")
     */
    protected $upcoming;

    /**
     * @Assert\Choice(callback={"DistributionBundle\Enum\AssistanceType", "values"})
     */
    protected $type;

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

    public function hasUpcomingOnly(): bool
    {
        return $this->has('upcoming');
    }

    public function getUpcomingOnly(): bool
    {
        return $this->upcoming;
    }

    public function hasType(): bool
    {
        return $this->has('type');
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function hasModalityTypes(): bool
    {
        return $this->has('modalityTypes');
    }

    /**
     * @return string[]
     */
    public function getModalityTypes(): array
    {
        return $this->modalityTypes;
    }
}
