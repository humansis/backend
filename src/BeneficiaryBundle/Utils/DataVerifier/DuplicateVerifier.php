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
                    $arrayTmp = [
                        "new" => $newBeneficiary,
                        "old" => $oldBeneficiary->getHousehold()->resetBeneficiaries()->addBeneficiary($oldBeneficiary)
                    ];


                    $listDuplicateBeneficiaries[] = $arrayTmp;
                    break;
                }
            }
        }

        if (!empty($listDuplicateBeneficiaries))
        {
            if (array_key_exists("id_tmp_cache", $householdArray))
                return [
                    "new_household" => $householdArray,
                    "id_tmp_cache" => $householdArray["id_tmp_cache"],
                    "data" => $listDuplicateBeneficiaries
                ];

            return [
                "new_household" => $householdArray,
                "data" => $listDuplicateBeneficiaries
            ];
        }

        return null;
    }
}