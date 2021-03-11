<?php
declare(strict_types=1);

namespace NewApiBundle\InputType;

use NewApiBundle\Request\FilterInputType\AbstractFilterInputType;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Assert\GroupSequence({"BeneficiaryFilterInputType", "Strict"})
 */
class BeneficiaryFilterInputType extends AbstractFilterInputType
{
    /**
     * @Assert\Type("array")
     * @Assert\All(
     *     constraints={
     *         @Assert\Type("int", groups={"Strict"})
     *     },
     *     groups={"Strict"}
     * )
     */
    protected $id;

    /**
     * @Assert\Type("string")
     */
    protected $fulltext;

    /**
     * @Assert\Type("string")
     * @Assert\Choice(callback={"DistributionBundle\Enum\AssistanceTargetType", "values"})
     */
    protected $assistanceTarget;

    public function hasIds(): bool
    {
        return $this->has('id');
    }

    public function getIds(): array
    {
        return $this->id;
    }

    public function hasFulltext(): bool
    {
        return $this->has('fulltext');
    }

    public function getFulltext()
    {
        return $this->fulltext;
    }

    public function hasAssistanceTarget()
    {
        return $this->has('assistanceTarget');
    }

    public function getAssistanceTarget()
    {
        return $this->assistanceTarget;
    }
}
