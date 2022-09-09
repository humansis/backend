<?php declare(strict_types=1);

namespace CommonBundle\DataFixtures\InputTypesGenerator;

use NewApiBundle\InputType\Beneficiary\AddressInputType;

class AddressGenerator
{
    public static function fromArray($address): AddressInputType
    {
        $addressInputType = new AddressInputType();
        $addressInputType->setLocationId($address['locationId']);
        $addressInputType->setStreet($address['street']);
        $addressInputType->setPostcode($address['postcode']);
        $addressInputType->setNumber($address['number']);

        return $addressInputType;
    }
}
