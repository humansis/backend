<?php

declare(strict_types=1);

namespace InputType;

use Symfony\Component\Validator\Constraints as Assert;

#[Assert\GroupSequence(['AddRemoveBeneficiaryToAssistanceInputType', 'Strict'])]
class AddRemoveBeneficiaryToAssistanceInputType extends AddRemoveAbstractBeneficiaryToAssistanceInputType
{
    /**
     * @Assert\All(
     *     constraints={
     *         @Assert\Type("int", groups={"Strict"})
     *     },
     *     groups={"Strict"}
     * )
     */
    #[Assert\Type('array')]
    protected $beneficiaryIds;

    public function setBeneficiaryIds($beneficiaryIds)
    {
        $this->beneficiaryIds = $beneficiaryIds;
    }

    public function getBeneficiaryIds()
    {
        return $this->beneficiaryIds;
    }
}
