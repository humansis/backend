<?php
declare(strict_types=1);

namespace InputType\Assistance;

use Request\InputTypeInterface;
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
    protected $documentNumbers = [];

    /**
     * @Assert\NotNull
     * @Enum(enumClass="Enum\NationalIdType")
     */
    protected $documentType;

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
    public function getDocumentNumbers(): array
    {
        return $this->documentNumbers;
    }

    /**
     * @param array $documentNumbers
     *
     * @return AssistanceBeneficiariesOperationInputType
     */
    public function setDocumentNumbers(array $documentNumbers): AssistanceBeneficiariesOperationInputType
    {
        $this->documentNumbers = $documentNumbers;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getDocumentType()
    {
        return $this->documentType;
    }

    /**
     * @param mixed $documentType
     *
     * @return AssistanceBeneficiariesOperationInputType
     */
    public function setDocumentType($documentType)
    {
        $this->documentType = $documentType;

        return $this;
    }






}
