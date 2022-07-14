<?php
namespace BeneficiaryBundle\Mapper;

use NewApiBundle\Entity\Address;
use CommonBundle\Mapper\LocationMapper;

class AddressMapper
{
    /** @var LocationMapper */
    private $locationMapper;

    /**
     * AddressMapper constructor.
     * @param LocationMapper $locationMapper
     */
    public function __construct(LocationMapper $locationMapper)
    {
        $this->locationMapper = $locationMapper;
    }

    public function toFlatArray(?Address $address): ?array
    {
        if (!$address) return null;
        return [
            "street" => $address->getStreet(),
            "number" => $address->getNumber(),
            "postcode" => $address->getPostcode(),
            "location" => $this->locationMapper->toFlatArray($address->getLocation()),
        ];
    }
}
