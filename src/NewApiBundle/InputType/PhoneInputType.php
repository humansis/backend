<?php

namespace NewApiBundle\InputType;

use Symfony\Component\Validator\Constraints as Assert;

class PhoneInputType
{
    /**
     * @Assert\Type("int")
     * @Assert\NotNull
     * @Assert\GreaterThan(0)
     */
    private $prefix;

    /**
     * @Assert\Type("int")
     * @Assert\NotNull
     * @Assert\GreaterThan(0)
     */
    private $number;

    /**
     * @Assert\Type("string")
     * @Assert\NotNull
     * @Assert\Choice(callback={"NewApiBundle\Enum\PhoneTypes", "values"}, strict=true)
     */
    private $type;

    /**
     * @Assert\Type("bool")
     * @Assert\NotNull
     */
    private $proxy;

    /**
     * @return int
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * @param int $prefix
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }

    /**
     * @return int
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * @param int $number
     */
    public function setNumber($number)
    {
        $this->number = $number;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return bool
     */
    public function getProxy()
    {
        return $this->proxy;
    }

    /**
     * @param bool $proxy
     */
    public function setProxy($proxy)
    {
        $this->proxy = $proxy;
    }
}
