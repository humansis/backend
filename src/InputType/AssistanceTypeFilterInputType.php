<?php

declare(strict_types=1);

namespace InputType;

use Request\FilterInputType\AbstractFilterInputType;
use Symfony\Component\Validator\Constraints as Assert;

class AssistanceTypeFilterInputType extends AbstractFilterInputType
{
    /**
     * @var string
     */
    #[Assert\Choice(callback: [\DBAL\SubSectorEnum::class, 'all'])]
    protected $subsector;

    public function getSubsector(): string
    {
        return $this->subsector;
    }

    public function hasSubsector(): bool
    {
        return $this->has('subsector');
    }
}
