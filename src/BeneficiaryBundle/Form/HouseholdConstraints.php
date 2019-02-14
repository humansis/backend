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
        $array = new Type('array');
        $null = new Type('null');
        $boolean = new Type('bool');
        $optionalBoolean = new Optional($boolean);
        $optionalNumeric = new Optional($numeric);
        $optionalString = new Optional($string);

        $household = [
            "project" => $optionalNumeric,
            "address_street" => $string,
            "address_number" => $string,
            "address_postcode" => $string,
            "livelihood" => $numeric,
            "notes" => $string,
            "latitude" => $string,
            "longitude" => $string,
            "__country" => $string,
            "location" => $array,
            "country_specific_answers" => $array,
            "beneficiaries" => $array,
        ];
        $location = [
            "country_iso3" => $optionalString,
            "adm1" => $string,
            "adm2" => $optionalString,
            "adm3" => $optionalString,
            "adm4" => $optionalString
        ];
        $countrySpecificAnswer = [
            "answer" => $string,
            "country_specific" => $array,
        ];
        $beneficiary = [
            "id" => $optionalNumeric,
            "id_tmp" => $optionalString,
            "given_name" => $string,
            "family_name" => $string,
            "gender" => $numeric,
            "status" => $numeric,
            "residency_status" => $string,
            "date_of_birth" => $string,
            "profile" => $array,
            "vulnerability_criteria" => $array,
            "phones" => $array,
            "national_ids" => $array
        ];
        $profile = [
            "id" => $optionalNumeric,
            "photo" => $string,
        ];
        $vulnerabilityCriterion = [
            "id" => $numeric,
        ];
        $phone = [
            "id" => $optionalNumeric,
            "number" => $string,
            "type" => $string,
            "prefix" => $string,
            "proxy" => $optionalBoolean,
        ];
        $nationalId = [
            "id" => $optionalNumeric,
            "id_number" => $string,
            "id_type" => $string,
        ];

        return [
            'household' => new Collection($household),
            'location' => new Collection($location),
            'country_specific_answer' => new Collection($countrySpecificAnswer),
            'beneficiary' => new Collection($beneficiary),
            'profile' => new Collection($profile),
            'vulnerabilityCriterion' => new Collection($vulnerabilityCriterion),
            'phone' => new Collection($phone),
            'nationalId' => new Collection($nationalId),
        ];
    }
}