<?php

declare(strict_types=1);

namespace NewApiBundle\InputType\Beneficiary;

use BeneficiaryBundle\Entity\Person;
use BeneficiaryBundle\Entity\Referral;
use NewApiBundle\Request\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Assert\GroupSequence({"BeneficiaryInputType", "Strict"})
 */
class BeneficiaryInputType implements InputTypeInterface
{
    /**
     * @Assert\Choice(callback={"BeneficiaryBundle\Enum\ResidencyStatus", "all"})
     * @Assert\NotBlank
     * @Assert\NotNull
     */
    private $residencyStatus;

    /**
     * @Assert\Choice(callback={"BeneficiaryBundle\Entity\Referral", "types"})
     * @Assert\Length(max="255")
     */
    private $referralType;

    /**
     * @Assert\Type("string")
     * @Assert\Length(max="255")
     */
    private $referralComment;

    /**
     * @Assert\Type("boolean")
     * @Assert\NotNull
     */
    private $isHead;

    /**
     * @Assert\Type("array")
     * @Assert\All(
     *     constraints={
     *         @Assert\Type("integer")
     *     },
     *     groups={"Strict"}
     * )
     */
    private $vulnerabilityCriteriaIds = [];

    /**
     * @var BeneficiaryPersonInputType
     * @Assert\Valid
     * @Assert\NotNull
     */
    private $person;

    /**
     * @return string one of ResidencyStatus values
     */
    public function getResidencyStatus()
    {
        return $this->residencyStatus;
    }

    /**
     * @param string $residencyStatus
     */
    public function setResidencyStatus($residencyStatus)
    {
        $this->residencyStatus = $residencyStatus;
    }

    /**
     * @return boolean
     */
    public function isHead()
    {
        return $this->isHead;
    }

    /**
     * @param boolean $isHead
     */
    public function setIsHead($isHead)
    {
        $this->isHead = $isHead;
    }

    /**
     * @return int[]
     */
    public function getVulnerabilityCriteriaIds()
    {
        return $this->vulnerabilityCriteriaIds;
    }

    /**
     * @param int[] $vulnerabilityCriteriaIds
     */
    public function setVulnerabilityCriteriaIds($vulnerabilityCriteriaIds)
    {
        $this->vulnerabilityCriteriaIds = $vulnerabilityCriteriaIds;
    }

    /**
     * @return BeneficiaryPersonInputType
     */
    public function getPerson()
    {
        return $this->person;
    }

    /**
     * @param BeneficiaryPersonInputType|null $person
     */
    public function setPerson(?BeneficiaryPersonInputType $person): void
    {
        $this->person = $person;
    }
}
