<?php


namespace BeneficiaryBundle\Utils\DataVerifier;

use BeneficiaryBundle\Entity\Household;

class LessVerifier extends AbstractVerifier
{

    /**
     * @param string $countryISO3
     * @param array $householdArray
     * @param int $cacheId
     * @param string $email
     * @return array|null
     */
    public function verify(string $countryISO3, array &$householdArray, int $cacheId, string $email)
    {
        if (! empty($householdArray['new']) && ! empty($householdArray['old']) &&
            count($householdArray['new']['beneficiaries']) < count($householdArray['old']['beneficiaries'])) {
            return $householdArray;
        }

        return null;
    }
}
