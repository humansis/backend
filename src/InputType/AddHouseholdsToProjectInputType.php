<?php

declare(strict_types=1);

namespace InputType;

use Request\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Assert\GroupSequence({"AddHouseholdsToProjectInputType", "Strict"})
 */
class AddHouseholdsToProjectInputType implements InputTypeInterface
{
    /**
     * @Assert\Type("array")
     * @Assert\NotNull
     * @Assert\All(
     *     constraints={
     *         @Assert\Type("integer", groups={"Strict"})
     *     },
     *     groups={"Strict"}
     * )
     */
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
