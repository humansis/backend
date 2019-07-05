<?php


namespace BeneficiaryBundle\Utils\DataVerifier;

interface InterfaceVerifier
{
    public function verify(string $countryISO3, array &$householdArray, int $cacheId, string $email);
}
