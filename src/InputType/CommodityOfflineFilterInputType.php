<?php

declare(strict_types=1);

namespace InputType;

use Request\FilterInputType\AbstractFilterInputType;
use Symfony\Component\Validator\Constraints as Assert;

#[Assert\GroupSequence(['CommodityOfflineFilterInputType', 'Strict'])]
class CommodityOfflineFilterInputType extends AbstractFilterInputType
{
    #[Assert\All(constraints: [
        new Assert\Type('string', groups: ['Strict']),
    ], groups: ['Strict'])]
    #[Assert\Type('array')]
    protected $notModalityTypes;

    public function hasNotModalityTypes(): bool
    {
        return $this->has('notModalityTypes');
    }

    public function getNotModalityTypes(): array
    {
        return $this->notModalityTypes;
    }
}
