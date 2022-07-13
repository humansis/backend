<?php

namespace CommonBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups as SymfonyGroups;

/**
 * Adm4
 * @deprecated use nested tree Location entity
 *
 * @see Adm1 For a better understanding of Adm
 *
 * @ORM\Table(name="adm4")
 * @ORM\Entity(repositoryClass="CommonBundle\Repository\Adm4Repository")
 */
class Adm4
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
     * @var Adm3
     *
     * @ORM\ManyToOne(targetEntity="CommonBundle\Entity\Adm3")
     * @SymfonyGroups({"FullHousehold", "SmallHousehold", "FullAssistance", "SmallAssistance", "FullVendor"})
     */
    private $adm3;

    /**
     * @var Location
     *
     * @ORM\OneToOne(targetEntity="CommonBundle\Entity\Location", cascade={"persist"})
     */
    private $location;

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=255, nullable=true)
     * @SymfonyGroups({"FullBeneficiary", "FullHousehold", "SmallHousehold", "FullAssistance", "SmallAssistance", "FullVendor"})
     */
    private $code;


    public function __construct(Adm3 $adm3)
    {
        $this->adm3 = $adm3;
        $this->location = new Location($adm3->getAdm2()->getAdm1()->getCountryISO3());
        $this->location->setLvl(4);
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
     * @return Adm4
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
     * Set adm3.
     *
     * @param \CommonBundle\Entity\Adm3|null $adm3
     *
     * @return Adm4
     */
    public function setAdm3(\CommonBundle\Entity\Adm3 $adm3 = null)
    {
        $this->adm3 = $adm3;

        return $this;
    }

    /**
     * Get adm3.
     *
     * @return \CommonBundle\Entity\Adm3|null
     */
    public function getAdm3()
    {
        return $this->adm3;
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

    /**
     * Set code.
     *
     * @param string $code
     *
     * @return Adm4
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
}
