<?php

declare(strict_types=1);

namespace InputType;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Assert\GroupSequence({"AddRemoveBeneficiaryToAssistanceInputType", "Strict"})
 */
class AddRemoveBeneficiaryToAssistanceInputType extends AddRemoveAbstractBeneficiaryToAssistanceInputType
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
