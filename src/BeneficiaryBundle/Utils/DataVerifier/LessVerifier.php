<?php


namespace BeneficiaryBundle\Utils\DataVerifier;


use BeneficiaryBundle\Entity\Household;

class LessVerifier extends AbstractVerifier
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
        if (count($householdArray['new']['beneficiaries']) < count($oldHousehold->getBeneficiaries()))
            return [
                'new' => $householdArray['new'],
                'old' => $oldHousehold
            ];

        return null;
    }
}