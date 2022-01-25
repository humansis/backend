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
     * @var bool
     * @Assert\Type("bool")
     */
    private $canSellFood = true;

    /**
     * @var bool
     * @Assert\Type("bool")
     */
    private $canSellNonFood = true;

    /**
     * @var bool
     * @Assert\Type("bool")
     */
    private $canSellCashback = true;

    /**
     * @var bool
     * @Assert\Type("bool")
     */
    private $canDoRemoteDistributions = false;

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

    /**
     * @return bool
     */
    public function isCanSellFood(): bool
    {
        return $this->canSellFood;
    }

    /**
     * @param bool $canSellFood
     */
    public function setCanSellFood($canSellFood): void
    {
        $this->canSellFood = $canSellFood;
    }

    /**
     * @return bool
     */
    public function isCanSellNonFood(): bool
    {
        return $this->canSellNonFood;
    }

    /**
     * @param bool $canSellNonFood
     */
    public function setCanSellNonFood($canSellNonFood): void
    {
        $this->canSellNonFood = $canSellNonFood;
    }

    /**
     * @return bool
     */
    public function isCanSellCashback(): bool
    {
        return $this->canSellCashback;
    }

    /**
     * @param bool $canSellCashback
     */
    public function setCanSellCashback($canSellCashback): void
    {
        $this->canSellCashback = $canSellCashback;
    }

    /**
     * @return bool
     */
    public function getCanDoRemoteDistributions(): bool
    {
        return $this->canDoRemoteDistributions;
    }

    /**
     * @param bool $canDoRemoteDistributions
     */
    public function setCanDoRemoteDistributions(bool $canDoRemoteDistributions): void
    {
        $this->canDoRemoteDistributions = $canDoRemoteDistributions;
    }
}
