<?php
declare(strict_types=1);

namespace NewApiBundle\InputType\FilterFragment;
use Symfony\Component\Validator\Constraints as Assert;

trait ModalityTypeFilterTrait
{
    /**
     * @Assert\Type("array")
     * @Assert\All(
     *     constraints={
     *         @Assert\Type("string", groups={"Strict"})
     *     },
     *     groups={"Strict"}
     * )
     */
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
