<?php


namespace BeneficiaryBundle\Utils\DataVerifier;


use BeneficiaryBundle\Entity\Beneficiary;

class DuplicateVerifier extends AbstractVerifier
{

    public function verify(string $countryISO3, array $householdArray)
    {
        $oldBeneficiaries = $this->em->getRepository(Beneficiary::class)->findByCriteria($countryISO3, []);

        $listDuplicateBeneficiaries = [];
        foreach ($householdArray['beneficiaries'] as $newBeneficiary)
        {
            $stringOldHousehold = strtolower(trim($newBeneficiary['given_name']) . "//" . trim($newBeneficiary['family_name']));
            /** @var Beneficiary $oldBeneficiary */
            foreach ($oldBeneficiaries as $oldBeneficiary)
            {
                if (
                    strtolower(trim($oldBeneficiary->getGivenName()) . "//" . trim($oldBeneficiary->getFamilyName()))
                    ===
                    $stringOldHousehold
                )
                {
                    $listDuplicateBeneficiaries[] = [
                        "new" => $newBeneficiary,
                        "old" => $oldBeneficiary->getHousehold()->resetBeneficiaries()->addBeneficiary($oldBeneficiary)
                    ];
                    break;
                }
            }
        }

        if (!empty($listDuplicateBeneficiaries))
            return ["new_household" => $householdArray, "data" => $listDuplicateBeneficiaries];
        return null;
    }
}