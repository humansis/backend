<?php

namespace InputType;

use Symfony\Component\Validator\Constraints as Assert;

class Country implements InputTypeInterface, \Stringable
{
    final public const HEADER_KEY = 'country';
    final public const REQUEST_KEY = '__country';

    /**
     * Country constructor.
     *
     * @param $iso3Code
     */
    public function __construct(#[Assert\NotBlank]
    #[Assert\Regex('/^[A-Z][A-Z][A-Z]$/i')]
    private readonly string $iso3Code)
    {
    }

    /**
     * @return string
     */
    public function getIso3()
    {
        return $this->iso3Code;
    }

    public function __toString(): string
    {
        return $this->iso3Code;
    }
}
