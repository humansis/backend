<?php

declare(strict_types=1);

namespace InputType\Assistance;

use Request\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Validator\Constraints\Enum;

#[Assert\GroupSequence(['AssistanceBeneficiariesOperationInputType', 'Strict'])]
class AssistanceBeneficiariesOperationInputType implements InputTypeInterface
{
    #[Assert\Type('string')]
    #[Assert\NotBlank]
    protected $justification;

    /**
     * @Assert\All(
     *     constraints={
     *         @Assert\Type("string", groups={"Strict"})
     *     },
     *     groups={"Strict"}
     * )
     */
    #[Assert\Type('array')]
    protected $documentNumbers = [];

    /**
     * @Enum(enumClass="Enum\NationalIdType")
     */
    protected $documentType;

    /**
     * @Assert\All(
     *     constraints={
     *         @Assert\Type("int", groups={"Strict"})
     *     },
     *     groups={"Strict"}
     * )
     */
    #[Assert\Type('array')]
    protected $beneficiaryIds = [];

    #[Assert\IsTrue(groups: 'Strict', message: 'Only one array can have values.')]
    public function isOneOfArraysNotEmpty(): bool
    {
        return (empty($this->beneficiaryIds) && !empty($this->documentNumbers))
            || (!empty($this->beneficiaryIds) && empty($this->documentNumbers));
    }

    #[Assert\IsTrue(groups: 'Strict', message: 'You must choose type of ID when using document numbers')]
    public function hasDocumentTypeWithPresentDocumentNumbers(): bool
    {
        if (empty($this->documentNumbers)) {
            return true;
        }

        return $this->documentType !== null;
    }

    public function setBeneficiaryIds($beneficiaryIds): AssistanceBeneficiariesOperationInputType
    {
        $this->beneficiaryIds = $beneficiaryIds;

        return $this;
    }

    public function getBeneficiaryIds()
    {
        return $this->beneficiaryIds;
    }

    /**
     * @return mixed
     */
    public function getJustification()
    {
        return $this->justification;
    }

    public function setJustification(mixed $justification): AssistanceBeneficiariesOperationInputType
    {
        $this->justification = $justification;

        return $this;
    }

    public function getDocumentNumbers(): array
    {
        return $this->documentNumbers;
    }

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

    public function setDocumentType(mixed $documentType): AssistanceBeneficiariesOperationInputType
    {
        $this->documentType = $documentType;

        return $this;
    }

    public function hasBeneficiaryIds(): bool
    {
        return !empty($this->beneficiaryIds);
    }

    public function hasDocumentNumbers(): bool
    {
        return !empty($this->documentNumbers);
    }
}
