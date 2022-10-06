<?php

declare(strict_types=1);

namespace InputType;

use InputType\FilterFragment\FulltextFilterTrait;
use InputType\FilterFragment\PrimaryIdFilterTrait;
use Request\FilterInputType\AbstractFilterInputType;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Assert\GroupSequence({"LocationFilterInputType", "Strict"})
 */
class LocationFilterInputType extends AbstractFilterInputType
{
    use PrimaryIdFilterTrait;
    use FulltextFilterTrait;

    /**
     * @var int
     * @Assert\Type(type="integer")
     */
    protected $level;

    /**
     * @var int
     * @Assert\Type(type="integer")
     */
    protected $parent;

    public function hasLevel(): bool
    {
        return $this->has('level');
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function hasParent(): bool
    {
        return $this->has('parent');
    }

    public function getParent(): int
    {
        return $this->parent;
    }
}
