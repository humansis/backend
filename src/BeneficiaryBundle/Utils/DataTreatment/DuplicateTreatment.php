<?php


namespace BeneficiaryBundle\Utils\DataTreatment;


use BeneficiaryBundle\Entity\Beneficiary;
use ProjectBundle\Entity\Project;

class DuplicateTreatment extends AbstractTreatment
{

    /**
     * if state = 0 && there is the key "new" => delete the household saved (with the id 'old_id')
     * else, we keep it
     *
     * @param Project $project
     * @param array $householdsArray
     * @return array
     * @throws \Exception
     */
    public function treat(Project $project, array $householdsArray)
    {
        $listHouseholds = [];
        foreach ($householdsArray as $householdData)
        {
            $newHousehold = $householdData['new_household'];
                dump($newHousehold);
            foreach ($householdData['data'] as $beneficiaryData)
            {
                if (array_key_exists('new', $beneficiaryData))
                {
                    if (intval($beneficiaryData['state']) === 0)
                    {
                        $beneficiary = $this->em->getRepository(Beneficiary::class)->find($beneficiaryData['id_old']);
                        if (!$beneficiary instanceof Beneficiary)
                            throw new \Exception("Beneficiary not found.");
                        $deleted = $this->beneficiaryService->remove($beneficiary);
                        if (!$deleted)
                            throw new \Exception("This beneficiary is head of household. You can't delete her/him");
                    }
                }
                else
                {
                    // we delete the beneficiary in the household which must be saved
                    foreach ($newHousehold['beneficiaries'] as $index => $newBeneficiary)
                    {
                        if ($newBeneficiary['given_name'] === $beneficiaryData['to_delete']['given_name']
                            && $newBeneficiary['family_name'] === $beneficiaryData['to_delete']['family_name'])
                        {
                            unset($newHousehold['beneficiaries'][$index]);
                            break;
                        }
                    }
                }
            }
            $listHouseholds[] = $newHousehold;
        }
        $this->em->flush();

        return $listHouseholds;
    }
}