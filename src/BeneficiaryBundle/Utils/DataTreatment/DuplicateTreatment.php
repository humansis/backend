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
        $listHouseholdsFromTypo = [];
        $this->getFromCache('mapping_new_old', $listHouseholdsFromTypo);
        foreach ($householdsArray as $householdData)
        {
            $newHousehold = $householdData['new_household'];
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
            //UPDATE THE NEW HH IN THE CACHE
            if (array_key_exists("id_tmp_cache", $householdData))
                $this->updateInCache($householdData["id_tmp_cache"], $newHousehold);
            $listHouseholds[] = $newHousehold;
        }
        $this->em->flush();
        $listHouseholdsFromCache = [];
        $this->getFromCache('mapping_new_old', $listHouseholdsFromCache);
        return $listHouseholdsFromCache;
    }

    /**
     * @param $step
     * @param array $listHouseholdsArray
     * @throws \Exception
     */
    private function getFromCache($step, array &$listHouseholdsArray)
    {
        if (null === $this->token)
            return;

        $dir_root = $this->container->get('kernel')->getRootDir();
        $dir_var = $dir_root . '/../var/data/' . $this->token;
        if (!is_dir($dir_var))
            mkdir($dir_var);

        $fileContent = file_get_contents($dir_var . '/' . $step);
        $householdsCached = json_decode($fileContent, true);
        foreach ($householdsCached as $householdCached)
        {
            $listHouseholdsArray[] = $householdCached;
        }
    }

    /**
     * @param $index
     * @param array $newHouseholdArray
     * @throws \Exception
     */
    private function updateInCache($index, array $newHouseholdArray)
    {
        if (null === $this->token)
            return;

        $dir_root = $this->container->get('kernel')->getRootDir();
        $dir_var = $dir_root . '/../var/data/' . $this->token;
        if (!is_dir($dir_var))
            mkdir($dir_var);
        if (!is_file($dir_var . '/mapping_new_old'))
        {
            file_put_contents($dir_var . '/mapping_new_old', json_encode([$index => ["new" => $newHouseholdArray, "old" => null]]));
        }
        else
        {
            $listHH = json_decode(file_get_contents($dir_var . '/mapping_new_old'), true);
            $listHH[$index]["new"] = $newHouseholdArray;
            file_put_contents($dir_var . '/mapping_new_old', json_encode($listHH));
        }
    }
}