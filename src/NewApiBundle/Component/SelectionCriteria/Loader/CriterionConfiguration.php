<?php declare(strict_types=1);

namespace NewApiBundle\Component\SelectionCriteria\Loader;

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

}
