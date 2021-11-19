<?php
declare(strict_types=1);

namespace NewApiBundle\InputType;

use NewApiBundle\InputType\FilterFragment\LocationFilterTrait;
use NewApiBundle\InputType\FilterFragment\ModalityTypeFilterTrait;
use NewApiBundle\InputType\FilterFragment\PrimaryIdFilterTrait;
use NewApiBundle\InputType\FilterFragment\ProjectFilterTrait;
use NewApiBundle\Request\FilterInputType\AbstractFilterInputType;
use Symfony\Component\Validator\Constraints as Assert;

class AssistanceFilterInputType extends AbstractFilterInputType
{
    use PrimaryIdFilterTrait;
    use ProjectFilterTrait;
    use LocationFilterTrait;
    use ModalityTypeFilterTrait;

    /**
     * @Assert\Type("boolean")
     */
    protected $upcoming;

    /**
     * @Assert\Choice(callback={"DistributionBundle\Enum\AssistanceType", "values"})
     */
    protected $type;

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

}
