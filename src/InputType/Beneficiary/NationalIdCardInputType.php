<?php

declare(strict_types=1);

namespace InputType\Beneficiary;

use Enum\NationalIdType;
use Request\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Validator\Constraints\Enum;

class NationalIdCardInputType implements InputTypeInterface
{
    /**
     * @Assert\Type("string")
     * @Assert\Length(max="255")
     * @Assert\NotBlank
     * @Assert\NotNull
     */
    private $number;

    /**
     * @Assert\NotNull
     * @Enum(enumClass="Enum\NationalIdType")
     */
    private $type;

    /**
     * @Assert\Type("integer")
     * @Assert\NotNull
     */
    private $priority = 1;

    /**
     * @param string $type
     * @param string $number
     *
     * @return NationalIdCardInputType
     */
    public static function create(string $type, string $number): NationalIdCardInputType
    {
        $self = new self();
        $self->setType($type);
        $self->setNumber($number);

        return $self;
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
    public function setNumber($number): void
    {
        $this->number = $number;
    }

    /**
     * @return string
     */
    public function getOriginalType(): string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return NationalIdType::valueFromAPI($this->type);
    }

    /**
     * @param string $type
     */
    public function setType($type): void
    {
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @param mixed $priority
     */
    public function setPriority($priority): void
    {
        $this->priority = $priority;
    }

}
