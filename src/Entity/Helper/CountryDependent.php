<?php

declare(strict_types=1);

namespace Entity\Helper;

use Doctrine\ORM\Mapping as ORM;

trait CountryDependent
{
    /**
     * @var string
     */
    #[ORM\Column(name: 'iso3', type: 'string', length: 3, nullable: false, options: ['fixed' => true])]
    private string $countryIso3;

    public function getCountryIso3(): string
    {
        return $this->countryIso3;
    }

    public function setCountryIso3(string $countryIso3): self
    {
        $this->countryIso3 = $countryIso3;

        return $this;
    }
}
