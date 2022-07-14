<?php

namespace NewApiBundle\Entity;

use CommonBundle\Entity\Location;
use Doctrine\ORM\Mapping as ORM;


/**
 * HouseholdLocation
 *
 * @ORM\Table(name="household_location")
 * @ORM\Entity(repositoryClass="BeneficiaryBundle\Repository\HouseholdLocationRepository")
 */
class HouseholdLocation
{
    const LOCATION_GROUP_CURRENT = 'current';
    const LOCATION_GROUP_RESIDENT = 'resident';
    
    const LOCATION_TYPE_SETTLEMENT = 'temporary_settlement';
    const LOCATION_TYPE_RESIDENCE = 'residence';
    const LOCATION_TYPE_CAMP = 'camp';

    const LOCATION_TYPES = [
        self::LOCATION_TYPE_CAMP,
        self::LOCATION_TYPE_RESIDENCE,
        self::LOCATION_TYPE_SETTLEMENT,
    ];

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
     * @ORM\Column(name="location_group", type="string", length=45)
     */
    private $locationGroup;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=45)
     */
    private $type;

    /**
     * @ORM\OneToOne(targetEntity="NewApiBundle\Entity\Address", cascade={"persist", "remove"})
     */
    private $address;

     /**
     * @ORM\OneToOne(targetEntity="NewApiBundle\Entity\CampAddress", cascade={"persist", "remove"})
     */
    private $campAddress;

     /**
     * @var Household
     *
     * @ORM\ManyToOne(targetEntity="NewApiBundle\Entity\Household", inversedBy="householdLocations")
     */
    private $household;

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
     * @param \NewApiBundle\Entity\Address|null $address
     *
     * @return HouseholdLocation
     */
    public function setAddress(\NewApiBundle\Entity\Address $address = null)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Get address.
     *
     * @return \NewApiBundle\Entity\Address|null
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Set campAddress.
     *
     * @param \NewApiBundle\Entity\CampAddress|null $campAddress
     *
     * @return HouseholdLocation
     */
    public function setCampAddress(\NewApiBundle\Entity\CampAddress $campAddress = null)
    {
        $this->campAddress = $campAddress;

        return $this;
    }

    /**
     * Get campAddress.
     *
     * @return \NewApiBundle\Entity\CampAddress|null
     */
    public function getCampAddress()
    {
        return $this->campAddress;
    }

    /**
     * Set household.
     *
     * @param \NewApiBundle\Entity\Household|null $household
     *
     * @return HouseholdLocation
     */
    public function setHousehold(\NewApiBundle\Entity\Household $household = null)
    {
        $this->household = $household;

        return $this;
    }

    /**
     * Get household.
     *
     * @return \NewApiBundle\Entity\Household|null
     */
    public function getHousehold()
    {
        return $this->household;
    }

     /**
     * Get the nested location of the household.
     *
     * @return Location|null
     */
    public function getLocation(): Location
    {
        if ($this->getType() === self::LOCATION_TYPE_CAMP) {
            return $this->getCampAddress()->getCamp()->getLocation();
        } else {
            return $this->getAddress()->getLocation();
        }
    }

}
