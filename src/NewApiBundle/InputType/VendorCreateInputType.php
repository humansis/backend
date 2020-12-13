<?php

namespace NewApiBundle\InputType;

use CommonBundle\InputType\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;

class VendorCreateInputType implements InputTypeInterface
{
    /**
     * @var string|null
     * @Assert\Length(max=255)
     */
    private $shop;

    /**
     * @var string
     * @Assert\Length(max=255)
     * @Assert\NotNull
     */
    private $name;

    /**
     * @var string
     * @Assert\Length(max=180)
     * @Assert\NotNull
     */
    private $username;

    /**
     * @var string
     * @Assert\Length(max=255)
     * @Assert\NotNull
     */
    private $salt;

    /**
     * @var string
     * @Assert\Length(max=255)
     * @Assert\NotNull
     */
    private $password;

    /**
     * @var string|null
     * @Assert\Length(max=255)
     */
    private $addressStreet;

    /**
     * @var string|null
     * @Assert\Length(max=255)
     */
    private $addressNumber;

    /**
     * @var string|null
     * @Assert\Length(max=255)
     */
    private $addressPostcode;

    /**
     * @return string|null
     */
    public function getShop(): ?string
    {
        return $this->shop;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @return string
     */
    public function getSalt(): string
    {
        return $this->salt;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @return string|null
     */
    public function getAddressStreet(): ?string
    {
        return $this->addressStreet;
    }

    /**
     * @return string|null
     */
    public function getAddressNumber(): ?string
    {
        return $this->addressNumber;
    }

    /**
     * @return string|null
     */
    public function getAddressPostcode(): ?string
    {
        return $this->addressPostcode;
    }

    /**
     * @param string|null $shop
     */
    public function setShop(?string $shop): void
    {
        $this->shop = $shop;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @param string $username
     */
    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    /**
     * @param string $salt
     */
    public function setSalt(string $salt): void
    {
        $this->salt = $salt;
    }

    /**
     * @param string $password
     */
    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    /**
     * @param string|null $addressStreet
     */
    public function setAddressStreet(?string $addressStreet): void
    {
        $this->addressStreet = $addressStreet;
    }

    /**
     * @param string|null $addressNumber
     */
    public function setAddressNumber(?string $addressNumber): void
    {
        $this->addressNumber = $addressNumber;
    }

    /**
     * @param string|null $addressPostcode
     */
    public function setAddressPostcode(?string $addressPostcode): void
    {
        $this->addressPostcode = $addressPostcode;
    }
}
