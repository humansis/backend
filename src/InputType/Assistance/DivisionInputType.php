<?php

declare(strict_types=1);

namespace InputType\Assistance;

use Component\Assistance\Enum\CommodityDivision;
use Request\InputTypeNullableDenormalizer;
use Symfony\Component\Validator\Constraints as Assert;
use Validator\Constraints\Enum;

#[Assert\GroupSequence(['DivisionInputType', 'Primary', 'Secondary', 'Tertiary'])]
class DivisionInputType implements InputTypeNullableDenormalizer
{
    #[Assert\Type('string')]
    #[Enum(options: [
        'enumClass' => "Component\Assistance\Enum\CommodityDivision",
    ])]
    private ?string $code = null;

    /**
     * @var DivisionGroupInputType[]|null
     */
    #[Assert\Valid]
    #[Assert\NotBlank(allowNull: true)]
    private ?array $quantities = null;

    #[Assert\IsTrue(message: "For selection 'Per Household Members' should be defined at least one group.", groups: ['Primary'])]
    public function isSetQuantitiesForGroups(): bool
    {
        if ($this->code !== CommodityDivision::PER_HOUSEHOLD_MEMBERS) {
            return true;
        }

        if (is_null($this->quantities) || count($this->quantities) === 0) {
            return false;
        } else {
            return true;
        }
    }

    #[Assert\IsTrue(message: "Property 'quantities' must be null for no-groups selection.", groups: ['Primary'])]
    public function isNotSetQuantities(): bool
    {
        if ($this->code === CommodityDivision::PER_HOUSEHOLD_MEMBERS) {
            return true;
        }

        if (is_null($this->quantities)) {
            return true;
        } else {
            return false;
        }
    }

    #[Assert\IsTrue(message: "For selection 'Per Household Members' should be defined one starting group from 1 Member", groups: ['Secondary'])]
    public function isStartingGroupExists(): bool
    {
        if ($this->code !== CommodityDivision::PER_HOUSEHOLD_MEMBERS) {
            return true;
        }

        $startingCount = 0;
        foreach ($this->quantities as $quantity) {
            if ($quantity->getRangeFrom() === 1) {
                $startingCount++;
            }
        }

        if ($startingCount === 1) {
            return true;
        } else {
            return false;
        }
    }

    #[Assert\IsTrue(message: "For selection 'Per Household Members' should be defined one Group which ends with null", groups: ['Secondary'])]
    public function isEndingGroupExists(): bool
    {
        if ($this->code !== CommodityDivision::PER_HOUSEHOLD_MEMBERS) {
            return true;
        }

        $endingCount = 0;
        foreach ($this->quantities as $quantity) {
            if ($quantity->getRangeTo() === null) {
                $endingCount++;
            }
        }

        if ($endingCount === 1) {
            return true;
        } else {
            return false;
        }
    }

    #[Assert\IsTrue(message: 'Groups must not overlap.', groups: ['Secondary'])]
    public function isGroupsOverlapping(): bool
    {
        if ($this->code !== CommodityDivision::PER_HOUSEHOLD_MEMBERS) {
            return true;
        }

        $i = 1;
        foreach ($this->quantities as $quantity) {
            $b = 1;
            foreach ($this->quantities as $quantitySub) {
                if ($i === $b) {
                    $b++;
                    continue;
                }

                if (
                    (
                        ($quantitySub->getRangeFrom() <= $quantity->getRangeFrom())
                        && ($quantity->getRangeFrom() <= $quantitySub->getRangeTo())
                    )
                    || (
                        ($quantitySub->getRangeFrom() <= $quantity->getRangeTo())
                        && ($quantity->getRangeTo() <= $quantitySub->getRangeTo())
                    )
                ) {
                    return false;
                }
                $b++;
            }
            $i++;
        }

        return true;
    }

    #[Assert\IsTrue(message: 'Groups ranges must follow up.', groups: ['Tertiary'])]
    public function isGroupsFollowing(): bool
    {
        if ($this->code !== CommodityDivision::PER_HOUSEHOLD_MEMBERS) {
            return true;
        }

        $prevTo = -1;
        foreach ($this->quantities as $quantity) {
            if ($prevTo === -1) {
                $prevTo = $quantity->getRangeTo();
                continue;
            }
            if ($quantity->getRangeFrom() - $prevTo !== 1) {
                return false;
            }
            $prevTo = $quantity->getRangeTo();
        }

        return true;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    /**
     * @return DivisionGroupInputType[]|null
     */
    public function getQuantities(): ?array
    {
        return $this->quantities;
    }

    public function addQuantity(DivisionGroupInputType $divisionGroupInputType): void
    {
        $this->quantities[] = $divisionGroupInputType;
    }

    public function removeQuantity(DivisionGroupInputType $divisionGroupInputType): void
    {
        // method must be declared to fulfill normalizer requirements
    }
}
