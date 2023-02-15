<?php

declare(strict_types=1);

namespace InputType\FilterFragment;

use Symfony\Component\Validator\Constraints as Assert;

trait ModalityTypeFilterTrait
{
    #[Assert\All(constraints: [
        new Assert\Type('string', groups: ['Strict']),
    ], groups: ['Strict'])]
    #[Assert\Type('array')]
    protected $modalityTypes;

    public function hasModalityTypes(): bool
    {
        return $this->has('modalityTypes');
    }

    /**
     * @return string[]
     */
    public function getModalityTypes(): array
    {
        return $this->modalityTypes;
    }
}
