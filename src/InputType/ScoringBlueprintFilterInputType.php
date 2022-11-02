<?php

declare(strict_types=1);

namespace InputType;

use Request\FilterInputType\AbstractFilterInputType;
use Symfony\Component\Validator\Constraints as Assert;

class ScoringBlueprintFilterInputType extends AbstractFilterInputType
{
    /**
     * @var string
     */
    #[Assert\Choice([true, false])]
    protected $archived;

    public function isArchived(): bool
    {
        return "true" === $this->archived;
    }

    public function setArchived(string $archived): ScoringBlueprintFilterInputType
    {
        $this->archived = $archived;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasArchived()
    {
        return $this->has('archived');
    }
}
