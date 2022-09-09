<?php
declare(strict_types=1);

namespace NewApiBundle\InputType\Assistance;

use NewApiBundle\Request\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Assert\GroupSequence({"AssistanceBeneficiariesOperationInputType", "Strict"})
 */
class AssistanceBeneficiariesOperationInputType implements InputTypeInterface
{

    /**
     * @Assert\Type("string")
     * @Assert\NotBlank
     */
    protected $justification;


    /**
     * @Assert\Type("array")
     * @Assert\All(
     *     constraints={
     *         @Assert\Type("string", groups={"Strict"})
     *     },
     *     groups={"Strict"}
     * )
     */
    protected $numbers = [];

    /**
     * @Assert\NotNull
     * @Enum(enumClass="NewApiBundle\Enum\NationalIdType")
     */
    protected $idType;

    /**
     * @return mixed
     */
    public function getJustification()
    {
        return $this->justification;
    }

    /**
     * @param mixed $justification
     *
     * @return AssistanceBeneficiariesOperationInputType
     */
    public function setJustification($justification): AssistanceBeneficiariesOperationInputType
    {
        $this->justification = $justification;

        return $this;
    }

    /**
     * @return array
     */
    public function getNumbers(): array
    {
        return $this->numbers;
    }

    /**
     * @param array $numbers
     *
     * @return AssistanceBeneficiariesOperationInputType
     */
    public function setNumbers(array $numbers): AssistanceBeneficiariesOperationInputType
    {
        $this->numbers = $numbers;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getIdType()
    {
        return $this->idType;
    }

    /**
     * @param mixed $idType
     *
     * @return AssistanceBeneficiariesOperationInputType
     */
    public function setIdType($idType): AssistanceBeneficiariesOperationInputType
    {
        $this->idType = $idType;

        return $this;
    }




}
