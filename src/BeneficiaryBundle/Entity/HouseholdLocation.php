<?php

namespace BeneficiaryBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * HouseholdLocation
 *
 * @ORM\Table(name="household_location")
 * @ORM\Entity(repositoryClass="BeneficiaryBundle\Repository\HouseholdLocationRepository")
 */
class HouseholdLocation
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="locationGroup", type="string", length=45)
     */
    private $locationGroup;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=45)
     */
    private $type;

    /**
     * @ORM\OneToOne(targetEntity="BeneficiaryBundle\Entity\Address", cascade={"persist", "remove"})
     */
    private $address;

     /**
     * @ORM\OneToOne(targetEntity="BeneficiaryBundle\Entity\CampAddress", cascade={"persist", "remove"})
     */
    private $campAddress;

   

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
     * Set locationGroup.
     *
     * @param string $locationGroup
     *
     * @return HouseholdLocation
     */
    public function setLocationGroup($locationGroup)
    {
        $this->locationGroup = $locationGroup;

        return $this;
    }

    /**
     * Get locationGroup.
     *
     * @return string
     */
    public function getLocationGroup()
    {
        return $this->locationGroup;
    }

    /**
     * Set type.
     *
     * @param string $type
     *
     * @return HouseholdLocation
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set address.
     *
     * @param \BeneficiaryBundle\Entity\Address|null $address
     *
     * @return HouseholdLocation
     */
    public function setAddress(\BeneficiaryBundle\Entity\Address $address = null)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Get address.
     *
     * @return \BeneficiaryBundle\Entity\Address|null
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Set campAddress.
     *
     * @param \BeneficiaryBundle\Entity\CampAddress|null $campAddress
     *
     * @return HouseholdLocation
     */
    public function setCampAddress(\BeneficiaryBundle\Entity\CampAddress $campAddress = null)
    {
        $this->campAddress = $campAddress;

        return $this;
    }

    /**
     * Get campAddress.
     *
     * @return \BeneficiaryBundle\Entity\CampAddress|null
     */
    public function getCampAddress()
    {
        return $this->campAddress;
    }  
}
