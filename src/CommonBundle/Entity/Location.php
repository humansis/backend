<?php

namespace CommonBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;

/**
 * Location
 *
 * @ORM\Table(name="location")
 * @ORM\Entity(repositoryClass="CommonBundle\Repository\LocationRepository")
 */
class Location
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Groups({"FullBeneficiary", "FullHousehold", "SmallHousehold", "FullDistribution", "FullVendor"})
     */
    private $id;

    /**
     * @var Adm1
     *
     * @ORM\OneToOne(targetEntity="CommonBundle\Entity\Adm1", mappedBy="location")
     * @Groups({"FullBeneficiary", "FullHousehold", "SmallHousehold", "FullDistribution", "FullVendor"})
     */
    private $adm1;

    /**
     * @var Adm2
     *
     * @ORM\OneToOne(targetEntity="CommonBundle\Entity\Adm2", mappedBy="location")
     * @Groups({"FullBeneficiary", "FullHousehold", "SmallHousehold", "FullDistribution", "FullVendor"})
     */
    private $adm2;

    /**
     * @var Adm3
     *
     * @ORM\OneToOne(targetEntity="CommonBundle\Entity\Adm3", mappedBy="location")
     * @Groups({"FullBeneficiary", "FullHousehold", "SmallHousehold", "FullDistribution", "FullVendor"})
     */
    private $adm3;

    /**
     * @var Adm4
     *
     * @ORM\OneToOne(targetEntity="CommonBundle\Entity\Adm4", mappedBy="location")
     * @Groups({"FullBeneficiary", "FullHousehold", "SmallHousehold", "FullDistribution", "FullVendor"})
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
     * Set adm1.
     *
     * @param \CommonBundle\Entity\Adm1|null $adm1
     *
     * @return Location
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
     * Set adm2.
     *
     * @param \CommonBundle\Entity\Adm2|null $adm2
     *
     * @return Location
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
     * Set adm3.
     *
     * @param \CommonBundle\Entity\Adm3|null $adm3
     *
     * @return Location
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
     * Set adm4.
     *
     * @param \CommonBundle\Entity\Adm4|null $adm4
     *
     * @return Location
     */
    public function setAdm4(\CommonBundle\Entity\Adm4 $adm4 = null)
    {
        $this->adm4 = $adm4;

        return $this;
    }

    /**
     * Get adm4.
     *
     * @return \CommonBundle\Entity\Adm4|null
     */
    public function getAdm4()
    {
        return $this->adm4;
    }

    
    public function getAdm1Name()
    {
        if (null !== $this->getAdm1()) {
            return $this->getAdm1()->getName();
        } elseif (null !== $this->getAdm2()) {
            return $this->getAdm2()->getAdm1()->getName();
        } elseif (null !== $this->getAdm3()) {
            return $this->getAdm3()->getAdm2()->getAdm1()->getName();
        } elseif (null !== $this->getAdm4()) {
            return $this->getAdm4()->getAdm3()->getAdm2()->getAdm1()->getName();
        } else {
            return "";
        }
    }

    public function getAdm2Name()
    {
        if (null !== $this->getAdm2()) {
            return $this->getAdm2()->getName();
        } elseif (null !== $this->getAdm3()) {
            return $this->getAdm3()->getAdm2()->getName();
        } elseif (null !== $this->getAdm4()) {
            return $this->getAdm4()->getAdm3()->getAdm2()->getName();
        } else {
            return "";
        }
    }

    public function getAdm3Name()
    {
        if (null !== $this->getAdm3()) {
            return $this->getAdm3()->getName();
        } elseif (null !== $this->getAdm4()) {
            return $this->getAdm4()->getAdm3()->getName();
        } else {
            return "";
        }
    }

    public function getAdm4Name()
    {
        if (null !== $this->getAdm4()) {
            return $this->getAdm4()->getName();
        } else {
            return "";
        }
    }

    public function getCode()
    {
        if ($this->getAdm1()) {
            return $this->getAdm1()->getCode();
        } elseif ($this->getAdm2()) {
            return $this->getAdm2()->getCode();
        } elseif ($this->getAdm3()) {
            return $this->getAdm3()->getCode();
        } elseif ($this->getAdm4()) {
            return $this->getAdm4()->getCode();
        }

    }
}
