<?php

namespace CommonBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups as SymfonyGroups;

/**
 * Adm2
 * @deprecated use nested tree Location entity
 *
 * @see Adm1 For a better understanding of Adm
 *
 * @ORM\Table(name="adm2")
 * @ORM\Entity(repositoryClass="CommonBundle\Repository\Adm2Repository")
 */
class Adm2
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
     * @var Adm1
     *
     * @ORM\ManyToOne(targetEntity="CommonBundle\Entity\Adm1")
     * @SymfonyGroups({"FullHousehold", "SmallHousehold", "FullAssistance", "SmallAssistance", "FullVendor"})
     */
    private $adm1;

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
     * @ORM\OneToOne(targetEntity="CommonBundle\Entity\Location", inversedBy="adm2", cascade={"persist"})
     */
    private $location;


    public function __construct(Adm1 $adm1)
    {
        $this->adm1 = $adm1;
        $this->location = new Location($adm1->getCountryISO3());
        $this->location->setLvl(2);
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
     * @return Adm2
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
     * Set adm1.
     *
     * @param \CommonBundle\Entity\Adm1|null $adm1
     *
     * @return Adm2
     */
    public function setAdm1(\CommonBundle\Entity\Adm1 $adm1 = null)
    {
        $this->adm1 = $adm1;

        return $this;
    }

    /**
     * Get adm1.
     *
     * @return \CommonBundle\Entity\Adm1|null
     */
    public function getAdm1()
    {
        return $this->adm1;
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
     * @return Adm2
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
