<?php
declare(strict_types=1);

namespace NewApiBundle\InputType\FilterFragment;
use NewApiBundle\Enum\SourceType;
use Symfony\Component\Validator\Constraints as Assert;

trait SourceFilterTrait
{
    /**
     * TODO: add validation from enum
     * @see SourceType
     * @Assert\Type("array")
     * @Assert\All(
     *     constraints={
     *         @Assert\Type("string", groups={"Strict"})
     *     },
     *     groups={"Strict"}
     * )
     */
    protected $sources;

    public function hasSources(): bool
    {
        return $this->has('sources');
    }

    public function getSources()
    {
        return $this->sources;
    }
}
