<?php

declare(strict_types=1);

namespace InputType;

use Request\FilterInputType\AbstractFilterInputType;
use Symfony\Component\Validator\Constraints as Assert;

class AssistanceTypeFilterInputType extends AbstractFilterInputType
{
    /**
     * @var string
     * @Assert\Choice(callback={"DBAL\SubSectorEnum", "all"})
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
