<?php

declare(strict_types=1);

namespace NewApiBundle\InputType;

use NewApiBundle\Request\FilterInputType\AbstractFilterInputType;
use Symfony\Component\Validator\Constraints as Assert;

class AssistanceTypeFilterInputType extends AbstractFilterInputType
{
    /**
     * @var string
     * @Assert\Type("string")
     */
    protected $subsector;

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->subsector;
    }

    /**
     * @return bool
     */
    public function hasType(): bool
    {
        return $this->has('subsector');
    }
}
