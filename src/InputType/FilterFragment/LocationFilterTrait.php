<?php

declare(strict_types=1);

namespace InputType\FilterFragment;

use Symfony\Component\Validator\Constraints as Assert;

trait LocationFilterTrait
{
    #[Assert\All(constraints: [new Assert\Type('integer', groups: ['Strict'])], groups: ['Strict'])]
    #[Assert\Type('array')]
    protected $locations;

    public function hasLocations(): bool
    {
        return $this->has('locations');
    }

    /**
     * @return int[]
     */
    public function getLocations()
    {
        return $this->locations;
    }
}
