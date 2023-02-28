<?php

declare(strict_types=1);

namespace InputType;

use Request\FilterInputType\AbstractFilterInputType;
use Symfony\Component\Validator\Constraints as Assert;

class ScoringBlueprintFilterInputType extends AbstractFilterInputType
{
    #[Assert\Choice([true, false])]
    protected bool $archived;

    public function isArchived(): bool
    {
        return $this->archived;
    }

    public function setArchived(bool $archived): ScoringBlueprintFilterInputType
    {
        $this->archived = $archived;

        return $this;
    }

    public function hasArchived(): bool
    {
        return $this->has('archived');
    }
}
