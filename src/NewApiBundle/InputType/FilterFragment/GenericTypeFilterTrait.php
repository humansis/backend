<?php
declare(strict_types=1);

namespace NewApiBundle\InputType\FilterFragment;
use NewApiBundle\Enum\SourceType;
use Symfony\Component\Validator\Constraints as Assert;

trait GenericTypeFilterTrait
{
    /**
     * TODO: add validation from enum
     *
     * @Assert\Type("string", groups={"Strict"})
     */
    protected $type;

    protected abstract function availableTypes(): array;

    public function hasType(): bool
    {
        return $this->has('type');
    }

    public function getType()
    {
        return $this->type;
    }
}
