<?php


namespace BeneficiaryBundle\Form;

use RA\RequestValidatorBundle\RequestValidator\Constraints as RequestValidatorConstraints;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\DateTime;
use Symfony\Component\Validator\Constraints\Optional;
use Symfony\Component\Validator\Constraints\Type;

class HouseholdConstraints extends RequestValidatorConstraints
{
    protected function configure(): array
    {
        $numeric = new Type('numeric');
        $string = new Type('string');
        $array = new Type('array');
        $null = new Type('null');
        $boolean = new Type('bool');
        $date = new DateTime(['format' => 'd-m-Y']);
        $optionalBoolean = new Optional($boolean);
        $optionalNumeric = new Optional($numeric);
        $optionalString = new Optional($string);
        $optionalArray = new Optional($array);
        $optionalDate = new Optional($date);

        $household = [
            "project" => $optionalNumeric,
            "livelihood" => $optionalString,
            "notes" => $string,
            "latitude" => $string,
            "longitude" => $string,
            "__country" => $string,
            "household_locations" => $array,
            "country_specific_answers" => $array,
            "beneficiaries" => $array,
            "income" => $optionalNumeric,
            "coping_strategies_index" => $optionalNumeric,
            "food_consumption_score" => $optionalNumeric,
            "assets" => $optionalArray,
            "shelter_status" => $optionalNumeric,
            "debt_level" => $optionalNumeric,
            "support_received_types" => $optionalArray,
            "support_date_received" => $optionalDate,
            "support_organization_name" => $optionalString,
            "income_spent_on_food" => $optionalNumeric,
            "household_income" => $optionalNumeric,
            "enumerator_name" => $optionalString,
            "proxy" => $optionalArray,
        ];
        $householdLocation = [
            "location_group" => $string,
            "type" => $string,
            "address" => $optionalArray,
            "camp_address" => $optionalArray,
        ];
        $location = [
            "country_iso3" => $optionalString,
            "adm1" => $numeric,
            "adm2" => $optionalNumeric,
            "adm3" => $optionalNumeric,
            "adm4" => $optionalNumeric
        ];
        $countrySpecificAnswer = [
            "answer" => $string,
            "country_specific" => $array,
        ];
        $beneficiary = [
            "id" => $optionalNumeric,
            "id_tmp" => $optionalString,
            "en_given_name" => $optionalString,
            "en_family_name" => $optionalString,
            "local_given_name" => $string,
            "local_family_name" => $string,
            "en_parents_name" => $optionalString,
            "local_parents_name" => $optionalString,
            "gender" => $numeric,
            "status" => $numeric,
            "residency_status" => $string,
            "date_of_birth" => $string,
            "profile" => $array,
            "vulnerability_criteria" => $array,
            "phones" => $array,
            "national_ids" => $array,
            "referral_type" => $optionalNumeric,
            "referral_comment" => $optionalString,
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
            'household_locations' => new Collection($householdLocation),
            'country_specific_answer' => new Collection($countrySpecificAnswer),
            'beneficiary' => new Collection($beneficiary),
            'profile' => new Collection($profile),
            'vulnerabilityCriterion' => new Collection($vulnerabilityCriterion),
            'phone' => new Collection($phone),
            'nationalId' => new Collection($nationalId),
        ];
    }
}
