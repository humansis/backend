<?php

declare(strict_types=1);

namespace NewApiBundle\InputType;

use NewApiBundle\Request\FilterInputType\AbstractFilterInputType;
use Symfony\Component\Validator\Constraints as Assert;

class BeneficiarySelectedFilterInputType extends AbstractFilterInputType
{
    private const EXCLUDE_ASSISTANCE = "excludeAssistance";

    /**
     * @Assert\Type("int")
     * @Assert\Positive()
     */
    protected $excludeAssistance;

    public function getExcludeAssistance()
    {
        return $this->excludeAssistance;
    }

    public function setExcludeAssistance($id)
    {
        $this->excludeAssistance = $id;
    }

    public function hasExcludeAssistance(): bool
    {
        return $this->has(self::EXCLUDE_ASSISTANCE);
    }
}
