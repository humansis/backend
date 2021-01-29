<?php

declare(strict_types=1);

namespace NewApiBundle\InputType;

use NewApiBundle\InputType\Household\NationalIdCardInputType;
use NewApiBundle\InputType\Household\PhoneInputType;
use NewApiBundle\Request\InputTypeInterface;
use NewApiBundle\Request\OrderInputType\AbstractSortInputType;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class CommunityUpdateInputType
 * @package NewApiBundle\InputType
 */
class CommunityUpdateInputType extends AbstractSortInputType implements InputTypeInterface
{

	const SORT_ID = 'id';
	const SORT_CONTACT_GIVEN_NAME = 'contactGivenName';
	const SORT_CONTACT_FAMILY_NAME = 'contactFamilyName';

    /**
     * @var string|null $longtitude
     *
     * @Assert\Length(max="45")
     * @Assert\Type("string")
     */
    private $longtitude;

    /**
     * @var string|null $latitude
     *
     * @Assert\Length(max="45")
     * @Assert\Type("string")
     */
    private $latitude;


    /**
     * @var string $contactGivenName
     *
     * @Assert\Length(max="255")
     * @Assert\Type("string")
     * @Assert\NotBlank()
     * @Assert\NotNull()
     */
    private $contactGivenName;

    /**
     * @var string $contactFamilyName
     *
     * @Assert\Length(max="255")
     * @Assert\Type("string")
     * @Assert\NotBlank()
     * @Assert\NotNull()
     */
    private $contactFamilyName;


    /**
     * @var AddressCreateInputType $address
     *
     * @Assert\Valid()
     */
    private $address;

    /**
     * @var NationalIdCardInputType $nationalIdCard
     *
     * @Assert\Valid()
     */
    private $nationalIdCard;

    /**
     * @var PhoneInputType $phone
     *
     * @Assert\Valid()
     */
    private $phone;

    /**
     * @return string|null
     */
    public function getLongtitude()
    {
        return $this->longtitude;
    }

    /**
     * @param string|null $longtitude
     */
    public function setLongtitude($longtitude)
    {
        $this->longtitude = $longtitude;
    }

    /**
     * @return string|null
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * @param string|null $latitude
     */
    public function setLatitude($latitude)
    {
        $this->latitude = $latitude;
    }

    /**
     * @return string
     */
    public function getContactGivenName()
    {
        return $this->contactGivenName;
    }

    /**
     * @param string $contactGivenName
     */
    public function setContactGivenName($contactGivenName): void
    {
        $this->contactGivenName = $contactGivenName;
    }

    /**
     * @return string
     */
    public function getContactFamilyName()
    {
        return $this->contactFamilyName;
    }

    /**
     * @param string $contactFamilyName
     */
    public function setContactFamilyName($contactFamilyName)
    {
        $this->contactFamilyName = $contactFamilyName;
    }

    /**
     * @return AddressCreateInputType
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @param AddressCreateInputType $address
     */
    public function setAddress(AddressCreateInputType $address)
    {
        $this->address = $address;
    }

    /**
     * @return NationalIdCardInputType
     */
    public function getNationalIdCard()
    {
        return $this->nationalIdCard;
    }

    /**
     * @param NationalIdCardInputType $nationalIdCard
     */
    public function setNationalIdCard(NationalIdCardInputType $nationalIdCard)
    {
        $this->nationalIdCard = $nationalIdCard;
    }

    /**
     * @return PhoneInputType
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * @param PhoneInputType $phone
     */
    public function setPhone(PhoneInputType $phone): void
    {
        $this->phone = $phone;
    }

    /**
	 * @inheritDoc
	 */
	protected function getValidNames(): array
	{
		return [
			self::SORT_ID,
			self::SORT_CONTACT_GIVEN_NAME,
			self::SORT_CONTACT_FAMILY_NAME,
		];
	}
}
