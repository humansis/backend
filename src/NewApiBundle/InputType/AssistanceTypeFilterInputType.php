<?php

declare(strict_types=1);

namespace NewApiBundle\InputType;

use NewApiBundle\Request\FilterInputType\AbstractFilterInputType;
use Symfony\Component\Validator\Constraints as Assert;

class AssistanceTypeFilterInputType extends AbstractFilterInputType
{
    /**
     * @var string
     * @Assert\Choice(callback={"NewApiBundle\DBAL\SubSectorEnum", "all"})
     */
    protected $subsector;

    /**
     * @return string
     */
    public function getSubsector(): string
    {
        return $this->subsector;
    }

    /**
     * @return bool
     */
    public function hasSubsector(): bool
    {
        return $this->has('subsector');
    }
}
