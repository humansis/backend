<?php

namespace Entity;

use Entity\Helper\StandardizedPrimaryKey;
use Doctrine\ORM\Mapping as ORM;


/**
 * HouseholdLocation
 *
 * @ORM\Table(name="household_location")
 * @ORM\Entity(repositoryClass="Repository\HouseholdLocationRepository")
 */
class HouseholdLocation
{
    use StandardizedPrimaryKey;

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
     * @ORM\OneToOne(targetEntity="Entity\Address", cascade={"persist", "remove"})
     */
    private $address;

     /**
     * @ORM\OneToOne(targetEntity="Entity\CampAddress", cascade={"persist", "remove"})
     */
    private $campAddress;

     /**
     * @var Household
     *
     * @ORM\ManyToOne(targetEntity="Entity\Household", inversedBy="householdLocations")
     */
    private $household;

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
     * @param \Entity\Address|null $address
     *
     * @return HouseholdLocation
     */
    public function setAddress(\Entity\Address $address = null)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Get address.
     *
     * @return \Entity\Address|null
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Set campAddress.
     *
     * @param \Entity\CampAddress|null $campAddress
     *
     * @return HouseholdLocation
     */
    public function setCampAddress(\Entity\CampAddress $campAddress = null)
    {
        $this->campAddress = $campAddress;

        return $this;
    }

    /**
     * Get campAddress.
     *
     * @return \Entity\CampAddress|null
     */
    public function getCampAddress()
    {
        return $this->campAddress;
    }

    /**
     * Set household.
     *
     * @param \Entity\Household|null $household
     *
     * @return HouseholdLocation
     */
    public function setHousehold(\Entity\Household $household = null)
    {
        $this->household = $household;

        return $this;
    }

    /**
     * Get household.
     *
     * @return \Entity\Household|null
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
