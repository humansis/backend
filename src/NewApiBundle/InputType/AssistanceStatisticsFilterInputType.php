<?php
declare(strict_types=1);

namespace NewApiBundle\InputType;

use NewApiBundle\Request\FilterInputType\AbstractFilterInputType;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Assert\GroupSequence({"AssistanceStatisticsFilterInputType", "PrimaryValidation", "SecondaryValidation"})
 */
class AssistanceStatisticsFilterInputType extends AbstractFilterInputType
{
    /**
     * @Assert\NotNull
     * @Assert\Type("array", groups={"PrimaryValidation"})
     * @Assert\All(
     *     constraints={
     *         @Assert\Type("int", groups={"SecondaryValidation"})
     *     },
     *     groups={"SecondaryValidation"}
     * )
     */
    protected $id;

    public function hasIds(): bool
    {
        return $this->has('id');
    }

    public function getIds(): array
    {
        return $this->id;
    }
}
