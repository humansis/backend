<?php

namespace CommonBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups as SymfonyGroups;

/**
 * Adm1
 * @deprecated use nested tree Location entity
 *
 * Adm are levels into the Boundaries standardisation
 * Adm0 => Country
 * Adm1 => under country
 * Adm2 => under under country
 * Adm3 => ...
 * Adm4 => ...
 * Adm5 => ...
 *
 * @ORM\Table(name="adm1")
 * @ORM\Entity(repositoryClass="CommonBundle\Repository\Adm1Repository")
 */
class Adm1
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @SymfonyGroups({"FullBeneficiary", "FullHousehold", "SmallHousehold", "FullAssistance", "FullInstitution", "SmallAssistance", "FullVendor"})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     * @SymfonyGroups({"FullBeneficiary", "FullHousehold", "SmallHousehold", "FullAssistance", "FullInstitution", "SmallAssistance", "FullVendor"})
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="countryISO3", type="string", length=3)
     * @SymfonyGroups({"FullBeneficiary", "FullHousehold", "SmallHousehold", "FullAssistance", "FullInstitution", "SmallAssistance", "FullVendor"})
     */
    private $countryISO3;

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=255, nullable=true)
     * @SymfonyGroups({"FullBeneficiary", "FullHousehold", "SmallHousehold", "FullAssistance", "SmallAssistance", "FullVendor"})
     */
    private $code;

    /**
     * @var Location
     *
     * @ORM\OneToOne(targetEntity="CommonBundle\Entity\Location", inversedBy="adm1", cascade={"persist"})
     */
    private $location;


    public function __construct(string $countryISO3)
    {
        $this->countryISO3 = $countryISO3;
        $this->location = new Location($countryISO3);
        $this->location->setLvl(1);
    }


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
     * Set name.
     *
     * @param string $name
     *
     * @return Adm1
     */
    public function setName($name)
    {
        $this->name = $name;
        $this->getLocation()->setName($name);

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set countryISO3.
     *
     * @param string $countryISO3
     *
     * @return Adm1
     */
    public function setCountryISO3($countryISO3)
    {
        $this->countryISO3 = $countryISO3;
        $this->getLocation()->setCountryISO3($countryISO3);

        return $this;
    }

    /**
     * Get countryISO3.
     *
     * @return string
     */
    public function getCountryISO3()
    {
        return $this->countryISO3;
    }

    /**
     * Set code.
     *
     * @param string $code
     *
     * @return Adm1
     */
    public function setCode($code)
    {
        $this->code = $code;
        $this->getLocation()->setCode($code);

        return $this;
    }

    /**
     * Get code.
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Get location.
     *
     * @return \CommonBundle\Entity\Location|null
     */
    public function getLocation()
    {
        return $this->location;
    }
}
