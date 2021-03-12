<?php
declare(strict_types=1);

namespace NewApiBundle\InputType;

use NewApiBundle\Request\FilterInputType\AbstractFilterInputType;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Assert\GroupSequence({"PersonFilterInputType", "PrimaryValidation", "SecondaryValidation"})
 */
class PersonFilterInputType extends AbstractFilterInputType
{
    /**
     * @Assert\Type("array", groups={"PrimaryValidation"})
     * @Assert\All(
     *     constraints={
     *         @Assert\Type("integer", groups={"SecondaryValidation"})
     *     },
     *     groups={"SecondaryValidation"}
     * )
     */
    protected $id;

    public function hasIds(): bool
    {
        return $this->has('id');
    }

    public function getIds(): ?array
    {
        return $this->id;
    }
}
