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
     * @Assert\NotNull
     */
    private $salt;

    /**
     * @Assert\Type("string")
     * @Assert\Length(max=255)
     */
    private $password;

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
     * @return string|null
     */
    public function getShop()
    {
        return $this->shop;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getSalt()
    {
        return $this->salt;
    }

    /**
     * @return string|null
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @return string|null
     */
    public function getAddressStreet()
    {
        return $this->addressStreet;
    }

    /**
     * @return string|null
     */
    public function getAddressNumber()
    {
        return $this->addressNumber;
    }

    /**
     * @return string|null
     */
    public function getAddressPostcode()
    {
        return $this->addressPostcode;
    }

    /**
     * @param string|null $shop
     */
    public function setShop($shop)
    {
        $this->shop = $shop;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @param string $salt
     */
    public function setSalt($salt)
    {
        $this->salt = $salt;
    }

    /**
     * @param string|null $password
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     * @param string|null $addressStreet
     */
    public function setAddressStreet($addressStreet)
    {
        $this->addressStreet = $addressStreet;
    }

    /**
     * @param string|null $addressNumber
     */
    public function setAddressNumber($addressNumber)
    {
        $this->addressNumber = $addressNumber;
    }

    /**
     * @param string|null $addressPostcode
     */
    public function setAddressPostcode($addressPostcode)
    {
        $this->addressPostcode = $addressPostcode;
    }

    /**
     * @return mixed
     */
    public function getLocationId()
    {
        return $this->locationId;
    }

    /**
     * @param mixed $locationId
     */
    public function setLocationId($locationId): void
    {
        $this->locationId = $locationId;
    }
}
