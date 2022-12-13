<?php

declare(strict_types=1);

namespace InputType\Assistance;

use Enum\ReliefPackageState;
use JetBrains\PhpStorm\Pure;
use Request\FilterInputType\AbstractFilterInputType;
use Symfony\Component\Validator\Constraints as Assert;
use Validator\Constraints\Iso8601;

#[Assert\GroupSequence(['VendorReliefPackageFilterInputType', 'Strict'])]
class VendorReliefPackageFilterInputType extends AbstractFilterInputType
{

    #[Assert\DateTime]
    protected ?string $lastModifiedFrom;


    #[Assert\Type('array')]
    #[Assert\All([
        new Assert\Choice(ReliefPackageState::RELIEF_PACKAGE_STATES),
    ])]
    protected array $states;

    #[Pure] public function hasLastModifiedFrom(): bool
    {
        return $this->has('lastModifiedFrom');
    }

    public function getLastModifiedFrom(): string
    {
        return $this->lastModifiedFrom;
    }

    #[Pure] public function hasStates(): bool
    {
        return $this->has('states');
    }

    public function getStates(): array
    {
        return $this->states;
    }

}
