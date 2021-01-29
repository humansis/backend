<?php

declare(strict_types=1);

namespace NewApiBundle\InputType;

use NewApiBundle\Request\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class AddressCreateInputType
 * @package NewApiBundle\InputType
 */
class AddressCreateInputType implements InputTypeInterface
{
    /**
     * @var string
     *
     * @Assert\Type("string")
     * @Assert\NotBlank()
     * @Assert\NotNull()
     * @Assert\Length(max="45")
     */
    public $number;

    /**
     * @var string
     *
     * @Assert\Type("string")
     * @Assert\NotBlank()
     * @Assert\NotNull()
     * @Assert\Length(max="45")
     */
    public $street;

    /**
     * @var string
     *
     * @Assert\Type("string")
     * @Assert\NotBlank()
     * @Assert\NotNull()
     * @Assert\Length(max="45")
     */
    public $postcode;

    /**
     * @var int
     *
     * @Assert\Type("integer")
     * @Assert\NotBlank()
     * @Assert\NotNull()
     */
    public $adm1Id;

    /**
     * @var int|null
     *
     * @Assert\Type("integer")
     */
    public $adm2Id;

    /**
     * @var int|null
     *
     * @Assert\Type("integer")
     */
    public $adm3Id;

    /**
     * @var int|null
     *
     * @Assert\Type("integer")
     */
    public $adm4Id;

    /**
     * @return string
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * @param string $number
     */
    public function setNumber($number)
    {
        $this->number = $number;
    }

    /**
     * @return string
     */
    public function getStreet()
    {
        return $this->street;
    }

    /**
     * @param string $street
     */
    public function setStreet($street)
    {
        $this->street = $street;
    }

    /**
     * @return string
     */
    public function getPostcode()
    {
        return $this->postcode;
    }

    /**
     * @param string $postcode
     */
    public function setPostcode($postcode)
    {
        $this->postcode = $postcode;
    }

    /**
     * @return int
     */
    public function getAdm1Id()
    {
        return $this->adm1Id;
    }

    /**
     * @param int $adm1
     */
    public function setAdm1Id($adm1)
    {
        $this->adm1Id = $adm1;
    }

    /**
     * @return int|null
     */
    public function getAdm2Id()
    {
        return $this->adm2Id;
    }

    /**
     * @param int|null $adm2
     */
    public function setAdm2Id($adm2)
    {
        $this->adm2Id = $adm2;
    }

    /**
     * @return int|null
     */
    public function getAdm3Id()
    {
        return $this->adm3Id;
    }

    /**
     * @param int|null $adm3
     */
    public function setAdm3Id($adm3)
    {
        $this->adm3Id = $adm3;
    }

    /**
     * @return int|null
     */
    public function getAdm4Id()
    {
        return $this->adm4Id;
    }

    /**
     * @param int|null $adm4
     */
    public function setAdm4Id($adm4)
    {
        $this->adm4Id = $adm4;
    }
}
