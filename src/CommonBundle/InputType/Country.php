<?php
namespace CommonBundle\InputType;

class Country implements InputTypeInterface
{
    /** @var string */
    private $iso3Code;

    /**
     * Country constructor.
     * @param $iso3Code
     */
    public function __construct(string $iso3Code)
    {
        $this->iso3Code = $iso3Code;
    }

    /**
     * @return string
     */
    public function getIso3()
    {
        return $this->iso3Code;
    }

    public function __toString()
    {
        return $this->iso3Code;
    }
}