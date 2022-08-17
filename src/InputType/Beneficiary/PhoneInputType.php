<?php

declare(strict_types=1);

namespace InputType\Beneficiary;

use Enum\PhoneTypes;
use Enum\VariableBool;
use Request\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Validator\Constraints\Enum;

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
     * @Enum(enumClass="Enum\PhoneTypes")
     */
    private $type;

    /**
     * @Enum(enumClass="Enum\VariableBool")
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
     * @return string|null
     * @throws \Enum\EnumValueNoFoundException
     */
    public function getType(): ?string
    {
        if (!$this->type) {
            return null;
        }

        return PhoneTypes::valueFromAPI($this->type);
    }

    /**
     * @param string|null $type
     */
    public function setType(?string $type)
    {
        $this->type = $type;
    }

    /**
     * @return boolean
     * @throws \Enum\EnumValueNoFoundException
     */
    public function getProxy()
    {
        if (empty($this->proxy)) {
            return false;
        }

        return VariableBool::valueFromAPI($this->proxy);
    }

    /**
     * @param boolean $proxy
     */
    public function setProxy($proxy)
    {
        $this->proxy = $proxy;
    }
}
