<?php

declare(strict_types=1);

namespace InputType;

use Request\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[Assert\GroupSequence(['RemoveBeneficiaryFromAssistanceInputType', 'Strict'])]
class RemoveBeneficiaryFromAssistanceInputType implements InputTypeInterface
{
    #[Assert\All(constraints: [new Assert\Type('int', groups: ['Strict'])], groups: ['Strict'])]
    #[Assert\Type('array')]
    protected $beneficiaryIds;

    #[Assert\Type('string')]
    #[Assert\NotBlank]
    #[Assert\NotNull]
    protected $justification;

    public function setBeneficiaryIds($beneficiaryIds)
    {
        $this->beneficiaryIds = $beneficiaryIds;
    }

    public function getBeneficiaryIds()
    {
        return $this->beneficiaryIds;
    }

    public function setJustification($justification)
    {
        $this->justification = $justification;
    }

    public function getJustification()
    {
        return $this->justification;
    }
}
