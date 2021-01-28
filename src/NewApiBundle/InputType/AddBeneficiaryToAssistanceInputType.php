<?php
declare(strict_types=1);

namespace NewApiBundle\InputType;

use NewApiBundle\Request\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Assert\GroupSequence({"AddBeneficiaryToAssistanceInputType", "Strict"})
 */
class AddBeneficiaryToAssistanceInputType implements InputTypeInterface
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

    /**
     * @Assert\Type("string")
     * @Assert\NotBlank
     * @Assert\NotNull
     */
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
