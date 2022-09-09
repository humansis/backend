<?php declare(strict_types=1);

namespace NewApiBundle\Entity\Helper;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups as SymfonyGroups;

trait CountryDependent
{
    /**
     * @var string
     *
     * @ORM\Column(name="iso3", type="string", nullable=false, length=3)
     * @SymfonyGroups({"FullProject", "FullUser", "FullCountrySpecific", "FullHousehold", "FullBooklet"})
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
    public function setCountryIso3(string $countryIso3): self
    {
        $this->countryIso3 = $countryIso3;

        return $this;
    }

}
