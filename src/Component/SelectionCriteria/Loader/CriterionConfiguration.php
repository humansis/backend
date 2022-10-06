<?php

declare(strict_types=1);

namespace Component\SelectionCriteria\Loader;

use Component\SelectionCriteria\Enum\CriteriaValueTransformerEnum;
use InvalidArgumentException;

class CriterionConfiguration
{
    /**
     * @var string
     */
    private $key;

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $target;

    /**
     * @var string
     */
    private $returnType;

    public function __construct(string $key, string $type, string $target, string $returnType)
    {
        $this->key = $key;
        $this->type = $type;
        $this->target = $target;
        $this->returnType = $returnType;
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getTarget(): string
    {
        return $this->target;
    }

    /**
     * @return string
     */
    public function getReturnType(): string
    {
        return $this->returnType;
    }

    /**
     * @param string $type
     * @return void
     */
    public function setReturnType(string $type): void
    {
        if (!in_array($type, CriteriaValueTransformerEnum::values())) {
            throw new InvalidArgumentException('invalid return type - "' . $type . '", has to be defined in CriteriaValueTransformerEnum');
        }

        $this->returnType = $type;
    }
}
