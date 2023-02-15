<?php

declare(strict_types=1);

namespace InputType;

use Request\FilterInputType\AbstractFilterInputType;
use Symfony\Component\Validator\Constraints as Assert;

#[Assert\GroupSequence(['CommodityFilterInputType', 'PrimaryValidation', 'SecondaryValidation'])]
class CommodityFilterInputType extends AbstractFilterInputType
{
    #[Assert\All(constraints: [new Assert\Type('integer', groups: ['SecondaryValidation'])], groups: ['SecondaryValidation'])]
    #[Assert\NotNull]
    #[Assert\Type('array', groups: ['PrimaryValidation'])]
    protected $id;

    public function hasIds(): bool
    {
        return $this->has('id');
    }

    public function getIds(): array
    {
        return $this->id;
    }
}
