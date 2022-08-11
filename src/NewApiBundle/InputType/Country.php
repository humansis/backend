<?php
namespace NewApiBundle\InputType;

use Symfony\Component\Validator\Constraints as Assert;

class Country implements InputTypeInterface
{
    const HEADER_KEY = 'country';
    const REQUEST_KEY = '__country';

    /**
     * @var string
     * @Assert\NotBlank()
     * @Assert\Regex("/^[A-Z][A-Z][A-Z]$/i")
     */
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
