<?php

declare(strict_types=1);

namespace InputType\FilterFragment;

use Enum\SourceType;
use Symfony\Component\Validator\Constraints as Assert;

trait GenericTypeFilterTrait
{
    /**
     * TODO: add validation from enum
     *
     * @Assert\Type("string", groups={"Strict"})
     */
    protected $type;

    abstract protected function availableTypes(): array;

    public function hasType(): bool
    {
        return $this->has('type');
    }

    public function getType()
    {
        return $this->type;
    }
}
