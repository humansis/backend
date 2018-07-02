<?php


namespace BeneficiaryBundle\Form;

use RA\RequestValidatorBundle\RequestValidator\Constraints as RequestValidatorConstraints;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\Optional;
use Symfony\Component\Validator\Constraints\Type;


class HouseholdConstraints extends RequestValidatorConstraints
{

    protected function configure() : array {
        $numeric = new Type('numeric');
        $string = new Type('string');
        $optionalNumeric = new Optional([$numeric]);
        $optionalString = new Optional([$string]);
        $household = [
            "address_street" => $string,
            "address_number" => $string,
            "address_postcode" => $string,
            "livelihood" => $numeric,
            "notes" => $string,
            "latitude" => $string,
            "longitude" => $string,
        ];
        $location = [
            "country_iso3" => $string,
            "adm1" => $string,
            "adm2" => $string,
            "adm3" => $string,
            "adm4" => $string
        ];
        $beneficiary = [
            "given_name" => $string,
            "family_name" => $string,
            "gender" => $string,
            "status" => $numeric,
            "date_of_birth" => $string,
            "updated_on" => $string
        ];
        $profile = [
            "photo" => $string,
        ];
        $vulnerabilityCriterion = [
            "id" => $numeric,
        ];
        $phone = [
            "number" => $string,
            "type" => $string,
        ];
        $nationalId = [
            "id_number" => $string,
            "id_type" => $string,
        ];

        return [
            'household' => new Collection($household),
            'location' => new Collection($location),
            'beneficiary' => new Collection($beneficiary),
            'profile' => new Collection($profile),
            'vulnerabilityCriterion' => new Collection($vulnerabilityCriterion),
            'phone' => new Collection($phone),
            'nationalId' => new Collection($nationalId),
        ];
    }
}