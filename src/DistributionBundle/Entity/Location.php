<?php

namespace DistributionBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;

/**
 * Location
 *
 * @ORM\Table(name="location")
 * @ORM\Entity(repositoryClass="DistributionBundle\Repository\LocationRepository")
 */
class Location
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Groups({"FullHousehold"})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="country_iso3", type="string", length=45)
     * @Groups({"FullHousehold"})
     */
    private $countryIso3;

    /**
     * @var string
     *
     * @ORM\Column(name="adm1", type="string", length=255)
     * @Groups({"FullHousehold"})
     */
    private $adm1;

    /**
     * @var string
     *
     * @ORM\Column(name="adm2", type="string", length=255)
     * @Groups({"FullHousehold"})
     */
    private $adm2;

    /**
     * @var string
     *
     * @ORM\Column(name="adm3", type="string", length=255)
     * @Groups({"FullHousehold"})
     */
    private $adm3;

    /**
     * @var string
     *
     * @ORM\Column(name="adm4", type="string", length=255)
     * @Groups({"FullHousehold"})
     */
    private $adm4;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set countryIso3.
     *
     * @param string $countryIso3
     *
     * @return Location
     */
    public function setCountryIso3($countryIso3)
    {
        $this->countryIso3 = $countryIso3;

        return $this;
    }

    /**
     * Get countryIso3.
     *
     * @return string
     */
    public function getCountryIso3()
    {
        return $this->countryIso3;
    }

    /**
     * Set adm1.
     *
     * @param string $adm1
     *
     * @return Location
     */
    public function setAdm1($adm1)
    {
        $this->adm1 = $adm1;

        return $this;
    }

    /**
     * Get adm1.
     *
     * @return string
     */
    public function getAdm1()
    {
        return $this->adm1;
    }

    /**
     * Set adm2.
     *
     * @param string $adm2
     *
     * @return Location
     */
    public function setAdm2($adm2)
    {
        $this->adm2 = $adm2;

        return $this;
    }

    /**
     * Get adm2.
     *
     * @return string
     */
    public function getAdm2()
    {
        return $this->adm2;
    }

    /**
     * Set adm3.
     *
     * @param string $adm3
     *
     * @return Location
     */
    public function setAdm3($adm3)
    {
        $this->adm3 = $adm3;

        return $this;
    }

    /**
     * Get adm3.
     *
     * @return string
     */
    public function getAdm3()
    {
        return $this->adm3;
    }

    /**
     * Set adm4.
     *
     * @param string $adm4
     *
     * @return Location
     */
    public function setAdm4($adm4)
    {
        $this->adm4 = $adm4;

        return $this;
    }

    /**
     * Get adm4.
     *
     * @return string
     */
    public function getAdm4()
    {
        return $this->adm4;
    }
}
