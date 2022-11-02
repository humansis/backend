<?php

namespace Entity;

use Entity\Location;
use Doctrine\ORM\Mapping as ORM;

/**
 * HouseholdLocation
 *
 * @ORM\Table(name="household_location")
 * @ORM\Entity(repositoryClass="Repository\HouseholdLocationRepository")
 */
class HouseholdLocation
{
    final public const LOCATION_GROUP_CURRENT = 'current';
    final public const LOCATION_GROUP_RESIDENT = 'resident';
    final public const LOCATION_TYPE_SETTLEMENT = 'temporary_settlement';
    final public const LOCATION_TYPE_RESIDENCE = 'residence';
    final public const LOCATION_TYPE_CAMP = 'camp';
    final public const LOCATION_TYPES = [
        self::LOCATION_TYPE_CAMP,
        self::LOCATION_TYPE_RESIDENCE,
        self::LOCATION_TYPE_SETTLEMENT,
    ];

    /**
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private int $id;

    /**
     * @ORM\Column(name="location_group", type="string", length=45)
     */
    private string $locationGroup;

    /**
     * @ORM\Column(name="type", type="string", length=45)
     */
    private string $type;

    /**
     * @ORM\OneToOne(targetEntity="Entity\Address", cascade={"persist", "remove"})
     */
    private $address;

    /**
     * @ORM\OneToOne(targetEntity="Entity\CampAddress", cascade={"persist", "remove"})
     */
    private $campAddress;

    /**
     * @ORM\ManyToOne(targetEntity="Entity\Household", inversedBy="householdLocations")
     */
    private ?\Entity\Household $household = null;

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
     * @param Address|null $address
     *
     * @return HouseholdLocation
     */
    public function setAddress(Address $address = null)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Get address.
     *
     * @return Address|null
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Set campAddress.
     *
     * @param CampAddress|null $campAddress
     *
     * @return HouseholdLocation
     */
    public function setCampAddress(CampAddress $campAddress = null)
    {
        $this->campAddress = $campAddress;

        return $this;
    }

    /**
     * Get campAddress.
     *
     * @return CampAddress|null
     */
    public function getCampAddress()
    {
        return $this->campAddress;
    }

    /**
     * Set household.
     *
     * @param Household|null $household
     *
     * @return HouseholdLocation
     */
    public function setHousehold(Household $household = null)
    {
        $this->household = $household;

        return $this;
    }

    /**
     * Get household.
     *
     * @return Household|null
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
