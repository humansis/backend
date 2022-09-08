<?php
declare(strict_types=1);

namespace NewApiBundle\InputType;

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
    protected $beneficiaryIds = [];

    /**
     * @Assert\Type("array")
     * @Assert\All(
     *     constraints={
     *         @Assert\Type("string", groups={"Strict"})
     *     },
     *     groups={"Strict"}
     * )
     */
    protected $nationalIds = [];

    public function setBeneficiaryIds($beneficiaryIds)
    {
        $this->beneficiaryIds = $beneficiaryIds;
    }

    public function getBeneficiaryIds()
    {
        return $this->beneficiaryIds;
    }

    /**
     * @param array $nationalIds
     *
     * @return AddRemoveBeneficiaryToAssistanceInputType
     */
    public function setNationalIds(array $nationalIds): AddRemoveBeneficiaryToAssistanceInputType
    {
        $this->nationalIds = $nationalIds;
        return $this;
    }


    public function getNationalIds()
    {
        return $this->nationalIds;
    }
}
