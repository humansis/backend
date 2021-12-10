<?php

declare(strict_types=1);

namespace NewApiBundle\InputType\Beneficiary;

use NewApiBundle\Enum\PhoneTypes;
use NewApiBundle\Request\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;

class PhoneInputType implements InputTypeInterface
{
    /**
     * @Assert\Type("string")
     * @Assert\Length(max="45")
     * @Assert\NotBlank
     * @Assert\NotNull
     */
    private $prefix;

    /**
     * @Assert\Type("string")
     * @Assert\Length(max="45")
     * @Assert\NotBlank
     * @Assert\NotNull
     */
    private $number;

    /**
     * @Assert\Type("string")
     * @Assert\Length(max="45")
     * @Assert\NotBlank
     * @Assert\NotNull
     */
    private $type;

    /**
     * @Assert\Type("boolean")
     * @Assert\NotNull
     */
    private $proxy;

    /**
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * @param string $prefix
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }

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
     * @Assert\Choice(callback={"\NewApiBundle\Enum\PhoneTypes", "values"}, strict=true, groups={"Strict"})
     * @return string
     */
    public function getType()
    {
        return PhoneTypes::valueFromAPI($this->type);
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return boolean
     */
    public function getProxy()
    {
        return $this->proxy;
    }

    /**
     * @param boolean $proxy
     */
    public function setProxy($proxy)
    {
        $this->proxy = $proxy;
    }
}
