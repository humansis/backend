<?php
declare(strict_types=1);

namespace NewApiBundle\InputType\Beneficiary;

use NewApiBundle\Request\FilterInputType\AbstractFilterInputType;
use Symfony\Component\Validator\Constraints as Assert;

class ProjectBeneficiariesFilterInputType extends AbstractFilterInputType
{
    /**
     * @Assert\Type("string")
     * @Assert\Choice(callback={"DistributionBundle\Enum\AssistanceTargetType", "values"})
     */
    protected $assistanceTarget;

    public function hasAssistanceTarget()
    {
        return $this->has('assistanceTarget');
    }

    public function getAssistanceTarget()
    {
        return $this->assistanceTarget;
    }
}
