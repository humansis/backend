<?php declare(strict_types=1);

namespace NewApiBundle\Entity\Helper;

use Doctrine\ORM\Mapping as ORM;

trait CountryDependent
{
    /**
     * @var string
     *
     * @ORM\Column(name="iso3", type="string", nullable=false)
     */
    private $countryIso3;

    /**
     * @return string
     */
    public function getCountryIso3(): string
    {
        return $this->countryIso3;
    }

    /**
     * @param string $countryIso3
     *
     * @return CountryDependent
     */
    public function setCountryIso3(string $countryIso3)
    {
        $this->countryIso3 = $countryIso3;

        return $this;
    }



}