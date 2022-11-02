<?php

declare(strict_types=1);

namespace Component\SelectionCriteria\Loader;

use Component\SelectionCriteria\Enum\CriteriaValueTransformerEnum;
use InvalidArgumentException;

class CriterionConfiguration
{
    public function __construct(private readonly string $key, private readonly string $type, private readonly string $target, private string $returnType)
    {
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getTarget(): string
    {
        return $this->target;
    }

    public function getReturnType(): string
    {
        return $this->returnType;
    }

    public function setReturnType(string $type): void
    {
        if (!in_array($type, CriteriaValueTransformerEnum::values())) {
            throw new InvalidArgumentException(
                'invalid return type - "' . $type . '", has to be defined in CriteriaValueTransformerEnum'
            );
        }

        $this->returnType = $type;
    }
}
