<?php

namespace NewApiBundle\InputType;

use NewApiBundle\Request\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;

class VendorUpdateInputType implements InputTypeInterface
{
    /**
     * @Assert\Type("string")
     * @Assert\Length(max=255)
     */
    private $shop;

    /**
     * @Assert\Type("string")
     * @Assert\Length(max=255)
     * @Assert\NotNull
     */
    private $name;

    /**
     * @Assert\Type("string")
     * @Assert\Length(max=255)
     */
    private $addressStreet;

    /**
     * @Assert\Type("string")
     * @Assert\Length(max=255)
     */
    private $addressNumber;

    /**
     * @Assert\Type("string")
     * @Assert\Length(max=255)
     */
    private $addressPostcode;

    /**
     * @Assert\Type("integer")
     * @Assert\NotNull
     */
    private $locationId;

    /**
     * @Assert\Type("integer")
     * @Assert\NotNull
     */
    private $userId;

    /**
     * @Assert\Type("string")
     * @Assert\Length(max=255)
     */
    private $vendorNo;

    /**
     * @Assert\Type("string")
     * @Assert\Length(max=255)
     */
    private $contractNo;

    /**
     * @return string|null
     */
    public function getShop()
    {
        return $this->shop;
    }

    /**
     * @param string|null $shop
     */
    public function setShop($shop)
    {
        $this->shop = $shop;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string|null
     */
    public function getAddressStreet()
    {
        return $this->addressStreet;
    }

    /**
     * @param string|null $addressStreet
     */
    public function setAddressStreet($addressStreet)
    {
        $this->addressStreet = $addressStreet;
    }

    /**
     * @return string|null
     */
    public function getAddressNumber()
    {
        return $this->addressNumber;
    }

    /**
     * @param string|null $addressNumber
     */
    public function setAddressNumber($addressNumber)
    {
        $this->addressNumber = $addressNumber;
    }

    /**
     * @return string|null
     */
    public function getAddressPostcode()
    {
        return $this->addressPostcode;
    }

    /**
     * @param string|null $addressPostcode
     */
    public function setAddressPostcode($addressPostcode)
    {
        $this->addressPostcode = $addressPostcode;
    }

    /**
     * @return int
     */
    public function getLocationId()
    {
        return $this->locationId;
    }

    /**
     * @param int $locationId
     */
    public function setLocationId($locationId)
    {
        $this->locationId = $locationId;
    }

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    /**
     * @return string|null
     */
    public function getVendorNo()
    {
        return $this->vendorNo;
    }

    /**
     * @param string|null $vendorNo
     */
    public function setVendorNo($vendorNo)
    {
        $this->vendorNo = $vendorNo;
    }

    /**
     * @return string|null
     */
    public function getContractNo()
    {
        return $this->contractNo;
    }

    /**
     * @param string|null $contractNo
     */
    public function setContractNo($contractNo): void
    {
        $this->contractNo = $contractNo;
    }
}
