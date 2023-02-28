<?php

declare(strict_types=1);

namespace InputType;

use Validator\Constraints\Country;
use Symfony\Component\Validator\Constraints as Assert;

class CountrySpecificCreateInputType extends CountrySpecificUpdateInputType
{
    /**
     * @Country
     */
    #[Assert\NotBlank]
    #[Assert\NotNull]
    private $iso3;

    /**
     * @return string
     */
    public function getIso3()
    {
        return $this->iso3;
    }

    public function setIso3($iso3)
    {
        $this->iso3 = $iso3;
    }
}
