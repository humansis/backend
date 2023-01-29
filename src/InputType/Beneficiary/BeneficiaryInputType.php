<?php

declare(strict_types=1);

namespace InputType\Beneficiary;

use DateTimeInterface;
use Entity\Referral;
use Enum\ResidencyStatus;
use Enum\HouseholdHead;
use Enum\PersonGender;
use Enum\VulnerabilityCriteria;
use InputType\Helper\EnumsBuilder;
use Request\InputTypeInterface;
use Utils\DateTime\Iso8601Converter;
use Validator\Constraints\Iso8601;
use Symfony\Component\Validator\Constraints as Assert;
use Validator\Constraints\Enum;

#[Assert\GroupSequence(['BeneficiaryInputType', 'Strict'])]
class BeneficiaryInputType implements InputTypeInterface
{
    #[Assert\Type('integer')]
    private ?int $id = null;

    #[Assert\NotBlank]
    #[Assert\NotNull]
    #[Iso8601]
    private $dateOfBirth;

    #[Assert\Type('string')]
    #[Assert\Length(max: 255)]
    #[Assert\NotBlank]
    #[Assert\NotNull]
    private $localFamilyName;

    #[Assert\Type('string')]
    #[Assert\Length(max: 255)]
    #[Assert\NotBlank]
    #[Assert\NotNull]
    private $localGivenName;

    #[Assert\Type('string')]
    #[Assert\Length(max: 255)]
    private $localParentsName;

    #[Assert\Type('string')]
    #[Assert\Length(max: 255)]
    private $enFamilyName;

    #[Assert\Type('string')]
    #[Assert\Length(max: 255)]
    private $enGivenName;

    #[Assert\Type('string')]
    #[Assert\Length(max: 255)]
    private $enParentsName;

    #[Assert\NotNull]
    #[Enum(options: [
        'enumClass' => "Enum\PersonGender",
    ])]
    private $gender;

    /**
     * @var NationalIdCardInputType[]
     */
    #[Assert\Type('array')]
    #[Assert\Valid]
    private array $nationalIdCards = [];

    /**
     * @var PhoneInputType[]
     */
    #[Assert\Type('array')]
    #[Assert\Valid]
    private array $phones = [];

    #[Assert\NotNull]
    #[Enum(options: [
        'enumClass' => "Enum\ResidencyStatus",
    ])]
    private $residencyStatus;

    #[Assert\Choice(callback: [Referral::class, 'types'], strict: true, groups: ['Strict'])]
    #[Assert\Length(max: 255)]
    private $referralType;

    #[Assert\Type('string')]
    #[Assert\Length(max: 255)]
    private $referralComment;

    #[Assert\NotNull]
    #[Enum(options: [
        'enumClass' => "Enum\HouseholdHead",
    ])]
    private $isHead;

    /**
     * @Assert\All(
     *     constraints={
     *         @Enum(enumClass="Enum\VulnerabilityCriteria")
     *     },
     *     groups={"Strict"}
     * )
     */
    #[Assert\Type('array')]
    private array $vulnerabilityCriteria  = [];

    /**
     * @return DateTimeInterface
     */
    #[Assert\NotNull]
    public function getDateOfBirth()
    {
        if (!$this->dateOfBirth) {
            return null;
        }

        return Iso8601Converter::toDateTime($this->dateOfBirth) ?: null;
    }

    /**
     * @param string $dateOfBirth
     */
    public function setDateOfBirth($dateOfBirth)
    {
        $this->dateOfBirth = $dateOfBirth;
    }

    /**
     * @return string
     */
    public function getLocalFamilyName()
    {
        return $this->localFamilyName;
    }

    /**
     * @param string $localFamilyName
     */
    public function setLocalFamilyName($localFamilyName)
    {
        $this->localFamilyName = $localFamilyName;
    }

    /**
     * @return string
     */
    public function getLocalGivenName()
    {
        return $this->localGivenName;
    }

    /**
     * @param string $localGivenName
     */
    public function setLocalGivenName($localGivenName)
    {
        $this->localGivenName = $localGivenName;
    }

    /**
     * @return string|null
     */
    public function getLocalParentsName()
    {
        return $this->localParentsName;
    }

    /**
     * @param string|null $localParentsName
     */
    public function setLocalParentsName($localParentsName)
    {
        $this->localParentsName = $localParentsName;
    }

    /**
     * @return string|null
     */
    public function getEnFamilyName()
    {
        return $this->enFamilyName;
    }

    /**
     * @param string|null $enFamilyName
     */
    public function setEnFamilyName($enFamilyName)
    {
        $this->enFamilyName = $enFamilyName;
    }

    /**
     * @return string|null
     */
    public function getEnGivenName()
    {
        return $this->enGivenName;
    }

    /**
     * @param string|null $enGivenName
     */
    public function setEnGivenName($enGivenName)
    {
        $this->enGivenName = $enGivenName;
    }

    /**
     * @return string|null
     */
    public function getEnParentsName()
    {
        return $this->enParentsName;
    }

    /**
     * @param string|null $enParentsName
     */
    public function setEnParentsName($enParentsName)
    {
        $this->enParentsName = $enParentsName;
    }

    /**
     * @return string
     * @see PersonGender::values()
     */
    public function getGender()
    {
        return PersonGender::valueFromAPI($this->gender);
    }

    /**
     * @param string $gender
     */
    public function setGender($gender)
    {
        $this->gender = $gender;
    }

    /**
     * @return NationalIdCardInputType[]
     */
    public function getNationalIdCards()
    {
        return $this->nationalIdCards;
    }

    public function addNationalIdCard(NationalIdCardInputType $nationalIdCard)
    {
        $this->nationalIdCards[] = $nationalIdCard;
    }

    public function removeNationalIdCard(NationalIdCardInputType $nationalIdCard)
    {
        // method must be declared to fullfill normalizer requirements
    }

    /**
     * @return PhoneInputType[]
     */
    public function getPhones()
    {
        return $this->phones;
    }

    public function addPhone(PhoneInputType $phone)
    {
        $this->phones[] = $phone;
    }

    public function removePhone(PhoneInputType $nationalIdCard)
    {
        // method must be declared to fullfill normalizer requirements
    }

    /**
     * @return string one of ResidencyStatus values
     */
    public function getResidencyStatus()
    {
        return $this->residencyStatus ? ResidencyStatus::valueFromAPI($this->residencyStatus) : null;
    }

    /**
     * @param string $residencyStatus
     */
    public function setResidencyStatus($residencyStatus)
    {
        $this->residencyStatus = $residencyStatus;
    }

    /**
     * @return string|null
     */
    public function getReferralType()
    {
        return $this->referralType;
    }

    /**
     * @param string|null $referralType
     */
    public function setReferralType($referralType)
    {
        $this->referralType = $referralType;
    }

    /**
     * @return string|null
     */
    public function getReferralComment()
    {
        return $this->referralComment;
    }

    /**
     * @param string|null $referralComment
     */
    public function setReferralComment($referralComment)
    {
        $this->referralComment = $referralComment;
    }

    public function hasReferral(): bool
    {
        return null !== $this->referralType
            && null !== $this->referralComment;
    }

    /**
     * @return bool
     */
    public function isHead()
    {
        return HouseholdHead::valueFromAPI($this->isHead);
    }

    public function setIsHead(bool|int|string $isHead)
    {
        $this->isHead = $isHead;
    }

    /**
     * @return array|null
     */
    public function getVulnerabilityCriteria(): array|null
    {
        $enumBuilder = new EnumsBuilder(VulnerabilityCriteria::class);
        $enumBuilder->setNullToEmptyArrayTransformation();

        return $enumBuilder->buildInputValues($this->vulnerabilityCriteria);
    }

    /**
     * @param string[] $vulnerabilityCriteria
     */
    public function setVulnerabilityCriteria(array $vulnerabilityCriteria): void
    {
        $this->vulnerabilityCriteria = $vulnerabilityCriteria;
    }

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int|null $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    #[Assert\IsTrue(message: 'Secondary ID Type has to be different then Primary ID Type.')]
    public function isSecondaryIdTypeDuplicity(): bool
    {
        if (count($this->nationalIdCards) < 2) {
            return true;
        }

        return $this->nationalIdCards[0]->getType() !== $this->nationalIdCards[1]->getType();
    }

    #[Assert\IsTrue(message: 'Tertiary ID Type has to be different then Primary ID Type and Secondary ID Type.')]
    public function isTertiaryIdTypeDuplicity(): bool
    {
        if (count($this->nationalIdCards) < 3) {
            return true;
        }

        return $this->nationalIdCards[0]->getType() !== $this->nationalIdCards[2]->getType() &&
            $this->nationalIdCards[1]->getType() !== $this->nationalIdCards[2]->getType();
    }
}
