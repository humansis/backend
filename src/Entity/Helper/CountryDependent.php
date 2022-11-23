<?php

declare(strict_types=1);

namespace Entity\Helper;

use Doctrine\ORM\Mapping as ORM;

trait CountryDependent
{
    /**
     * @var string
     *
     * @ORM\Column(name="iso3", type="string", nullable=false, length=3, options={"fixed" = true})
     */
    private $countryIso3;

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
