<?php

declare(strict_types=1);

namespace InputType;

use Request\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[Assert\GroupSequence(['AddHouseholdsToProjectInputType', 'Strict'])]
class AddHouseholdsToProjectInputType implements InputTypeInterface
{
    #[Assert\All(constraints: [new Assert\Type('integer', groups: ['Strict'])], groups: ['Strict'])]
    #[Assert\Type('array')]
    #[Assert\NotNull]
    private $householdIds;

    /**
     * @return int[]
     */
    public function getHouseholdIds()
    {
        return $this->householdIds;
    }

    /**
     * @param int[] $householdIds
     */
    public function setHouseholdIds($householdIds)
    {
        $this->householdIds = $householdIds;
    }
}
