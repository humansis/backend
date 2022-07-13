<?php

declare(strict_types=1);

namespace NewApiBundle\InputType;

use NewApiBundle\Request\FilterInputType\AbstractFilterInputType;
use Symfony\Component\Validator\Constraints as Assert;

class ScoringBlueprintFilterInputType extends AbstractFilterInputType
{

    /**
     * @var string
     * @Assert\Choice({"true", "false"})
     */
    protected $archived;

    /**
     * @return bool
     */
    public function isArchived(): bool
    {
        return "true" === $this->archived;
    }

    /**
     * @param string $archived
     *
     * @return ScoringBlueprintFilterInputType
     */
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
