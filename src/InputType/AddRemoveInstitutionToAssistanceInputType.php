<?php

declare(strict_types=1);

namespace InputType;

use Symfony\Component\Validator\Constraints as Assert;

#[Assert\GroupSequence(['AddRemoveInstitutionToAssistanceInputType', 'Strict'])]
class AddRemoveInstitutionToAssistanceInputType extends AddRemoveAbstractBeneficiaryToAssistanceInputType
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
    protected $institutionIds;

    public function setInstitutionIds($institutionIds)
    {
        $this->institutionIds = $institutionIds;
    }

    public function getInstitutionIds()
    {
        return $this->institutionIds;
    }
}
