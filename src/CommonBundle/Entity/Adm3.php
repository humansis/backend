<?php

namespace CommonBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups as SymfonyGroups;

/**
 * Adm3
 * @deprecated use nested tree Location entity
 *
 * @see Adm1 For a better understanding of Adm
 *
 * @ORM\Table(name="adm3")
 * @ORM\Entity(repositoryClass="CommonBundle\Repository\Adm3Repository")
 */
class Adm3
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
     * @var Adm2
     *
     * @ORM\ManyToOne(targetEntity="CommonBundle\Entity\Adm2")
     * @SymfonyGroups({"FullHousehold", "SmallHousehold", "FullAssistance", "SmallAssistance", "FullVendor"})
     */
    private $adm2;

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
     * @ORM\OneToOne(targetEntity="CommonBundle\Entity\Location", inversedBy="adm3", cascade={"persist"})
     */
    private $location;


    public function __construct()
    {
        $this->location = new Location();
        $this->location->setLvl(3);
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
     * @return Adm3
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
     * Set adm2.
     *
     * @param \CommonBundle\Entity\Adm2|null $adm2
     *
     * @return Adm3
     */
    public function setAdm2(\CommonBundle\Entity\Adm2 $adm2 = null)
    {
        $this->adm2 = $adm2;

        return $this;
    }

    /**
     * Get adm2.
     *
     * @return \CommonBundle\Entity\Adm2|null
     */
    public function getAdm2()
    {
        return $this->adm2;
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
     * @return Adm3
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
