<?php

declare(strict_types=1);

namespace NewApiBundle\InputType;

use NewApiBundle\Request\FilterInputType\AbstractFilterInputType;
use Symfony\Component\Validator\Constraints as Assert;

class BeneficiarySelectedFilterInputType extends AbstractFilterInputType
{
    /**
     * @Assert\Type("int")
     * @Assert\Positive()
     */
    protected $excludeAssistance;

    public function getId()
    {
        return $this->excludeAssistance;
    }

    public function setId($id)
    {
        $this->excludeAssistance = $id;
    }
}
