<?php

declare(strict_types=1);

namespace NewApiBundle\InputType;

use NewApiBundle\InputType\Beneficiary\NationalIdCardInputType;
use NewApiBundle\InputType\Beneficiary\PhoneInputType;
use NewApiBundle\Request\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;

class HouseholdProxyInputType implements InputTypeInterface
{
    /**
     * @Assert\Type("string")
     */
    private $enGivenName;

    /**
     * @Assert\Type("string")
     */
    private $enFamilyName;

    /**
     * @Assert\Type("string")
     * @Assert\NotNull
     */
    private $localGivenName;

    /**
     * @Assert\Type("string")
     * @Assert\NotNull
     */
    private $localFamilyName;

    /**
     * @Assert\Type("string")
     */
    private $localParentsName;

    /**
     * @Assert\Type("string")
     */
    private $enParentsName;

    /**
     * @Assert\Type("array")
     * @Assert\Valid
     */
    private $nationalIdCards = [];

    /**
     * @Assert\Type("array")
     * @Assert\Valid
     */
    private $phones = [];

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
}
