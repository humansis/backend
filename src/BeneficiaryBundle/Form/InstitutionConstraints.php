<?php


namespace BeneficiaryBundle\Form;

use RA\RequestValidatorBundle\RequestValidator\Constraints as RequestValidatorConstraints;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\Optional;
use Symfony\Component\Validator\Constraints\Type;

class InstitutionConstraints extends RequestValidatorConstraints
{
    protected function configure() : array
    {
        $numeric = new Type('numeric');
        $string = new Type('string');
        $array = new Type('array');
        $null = new Type('null');
        $boolean = new Type('bool');
        $optionalBoolean = new Optional($boolean);
        $optionalNumeric = new Optional($numeric);
        $optionalString = new Optional($string);
        $optionalArray = new Optional($array);

        $location = [
            "country_iso3" => $optionalString,
            "adm1" => $optionalNumeric,
            "adm2" => $optionalNumeric,
            "adm3" => $optionalNumeric,
            "adm4" => $optionalNumeric
        ];
        $address = [
            "street" => $optionalString,
            "number" => $optionalString,
            "postcode" => $optionalString,
            "location" => new Collection($location),
        ];
        $institution = [
            'type' => $string,
            'address' => $optionalArray,
            'latitude' => $optionalString,
            'longitude' => $optionalString,
            'id_type' => $optionalString,
            'id_number' => $optionalString,
            'phone_prefix' => $optionalString,
            'phone_number' => $optionalString,
            'contact_name' => $optionalString,
        ];

        return [
            'institution' => new Collection($institution),
            'address' => new Collection($address),
        ];
    }
}
