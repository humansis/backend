<?php


namespace BeneficiaryBundle\Utils\DataVerifier;


use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Household;

class MoreVerifier extends AbstractVerifier
{

    /**
     * @param string $countryISO3
     * @param array $householdArray
     * @param int $cacheId
     * @return array|null
     */
    public function verify(string $countryISO3, array $householdArray, int $cacheId)
    {
        /** @var Household $oldHousehold */
        $oldHousehold = $this->em->getRepository(Household::class)->find($householdArray['old']['id']);
        $oldBeneficiaries = $this->em->getRepository(Beneficiary::class)->findByHousehold($oldHousehold);
        if (count($householdArray['new']['beneficiaries']) > count($oldBeneficiaries))
            return [
                'new' => $householdArray['new'],
                'old' => $oldHousehold
            ];

        return null;
    }
}