<?php
declare(strict_types=1);

namespace NewApiBundle\InputType;

use BeneficiaryBundle\Entity\Person;
use BeneficiaryBundle\Entity\Referral;
use DateTime;
use DateTimeInterface;
use Exception;
use InvalidArgumentException;
use NewApiBundle\InputType\Beneficiary\NationalIdCardInputType;
use NewApiBundle\InputType\Beneficiary\PhoneInputType;
use Symfony\Component\Validator\Constraints as Assert;

class PersonInputType
{
    /**
     * @Assert\Type("string")
     * @Assert\Length(max="255")
     */
    protected $enGivenName;

    /**
     * @Assert\Type("string")
     * @Assert\Length(max="255")
     */
    protected $enFamilyName;

    /**
     * @Assert\Type("string")
     * @Assert\Length(max="255")
     */
    protected $enParentsName;

    /**
     * @Assert\Type("string")
     * @Assert\Length(max="255")
     */
    protected $localGivenName;

    /**
     * @Assert\Type("string")
     * @Assert\Length(max="255")
     */
    protected $localFamilyName;

    /**
     * @Assert\Type("string")
     * @Assert\Length(max="255")
     */
    protected $localParentsName;

    /**
     * @Assert\Type("integer")
     */
    protected $profileId;

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
     * @Assert\Choice({"M", "F"})
     */
    protected $gender;

    /**
     * @Assert\Date
     */
    protected $dateOfBirth;

    /**
     * @Assert\Type("array")
     * @Assert\Valid
     */
    protected $nationalIdCards = [];

    /**
     * @Assert\Type("array")
     * @Assert\Valid
     */
    protected $phones = [];

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
    public function setEnGivenName($enGivenName): void
    {
        $this->enGivenName = $enGivenName;
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
    public function setEnFamilyName($enFamilyName): void
    {
        $this->enFamilyName = $enFamilyName;
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
    public function setEnParentsName($enParentsName): void
    {
        $this->enParentsName = $enParentsName;
    }

    /**
     * @return string|null
     */
    public function getLocalGivenName()
    {
        return $this->localGivenName;
    }

    /**
     * @param string|null $localGivenName
     */
    public function setLocalGivenName($localGivenName): void
    {
        $this->localGivenName = $localGivenName;
    }

    /**
     * @return string|null
     */
    public function getLocalFamilyName()
    {
        return $this->localFamilyName;
    }

    /**
     * @param string|null $localFamilyName
     */
    public function setLocalFamilyName($localFamilyName): void
    {
        $this->localFamilyName = $localFamilyName;
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
    public function setLocalParentsName($localParentsName): void
    {
        $this->localParentsName = $localParentsName;
    }

    /**
     * @return integer|null
     */
    public function getProfileId()
    {
        return $this->profileId;
    }

    /**
     * @param integer|null $profileId
     */
    public function setProfileId($profileId): void
    {
        $this->profileId = $profileId;
    }

    /**
     * @return int|null
     */
    public function getGender()
    {
        if (null === $this->gender) {
            return null;
        }

        if ('M' === $this->gender) {
            return Person::GENDER_MALE;
        } elseif ('F' === $this->gender) {
            return Person::GENDER_FEMALE;
        }

        throw new InvalidArgumentException('Invalid gender');
    }

    /**
     * @param string|null $gender
     */
    public function setGender($gender): void
    {
        $this->gender = $gender;
    }

    /**
     * @return DateTimeInterface
     * @throws Exception
     */
    public function getDateOfBirth()
    {
        return new DateTime($this->dateOfBirth);
    }

    /**
     * @param string $dateOfBirth
     */
    public function setDateOfBirth($dateOfBirth): void
    {
        $this->dateOfBirth = $dateOfBirth;
    }

    /**
     * @return NationalIdCardInputType[]
     */
    public function getNationalIdCards()
    {
        return $this->nationalIdCards;
    }

    /**
     * @param NationalIdCardInputType $nationalIdCard
     */
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

    /**
     * @param PhoneInputType $phone
     */
    public function addPhone(PhoneInputType $phone)
    {
        $this->phones[] = $phone;
    }

    public function removePhone(PhoneInputType $nationalIdCard)
    {
        // method must be declared to fullfill normalizer requirements
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
    public function setReferralType($referralType): void
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
    public function setReferralComment($referralComment): void
    {
        $this->referralComment = $referralComment;
    }
}
