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
        $listHouseholdsFromNotTypo = [];
        $this->getFromCache('mapping_new_old', $listHouseholdsFromTypo);
        $this->getFromCache('no_typo', $listHouseholdsFromNotTypo);
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
        $dir_file = $dir_var . '/' . $step;
        if (!is_file($dir_file))
            return;

        $fileContent = file_get_contents($dir_file);
        $householdsCached = json_decode($fileContent, true);
        foreach ($householdsCached as $householdCached)
        {
            $listHouseholdsArray[] = $householdCached;
        }
    }

    /**
     * @param $idCache
     * @param array $newHouseholdArray
     * @throws \Exception
     */
    private function updateInCache($idCache, array $newHouseholdArray)
    {
        if (null === $this->token)
            return;

        $dir_root = $this->container->get('kernel')->getRootDir();
        $dir_var = $dir_root . '/../var/data/' . $this->token;
        if (!is_dir($dir_var))
            mkdir($dir_var);

        $dir_file_mapping = $dir_var . '/mapping_new_old';
        if (is_file($dir_file_mapping))
        {
            $listHH = json_decode(file_get_contents($dir_file_mapping), true);
            if (array_key_exists($idCache, $listHH))
            {
                $listHH[$idCache]["new"] = $newHouseholdArray;
                file_put_contents($dir_file_mapping, json_encode($listHH));
                return;
            }
        }

        $dir_file_not_typo = $dir_var . '/no_typo';
        if (is_file($dir_file_not_typo))
        {
            $listHH = json_decode(file_get_contents($dir_file_not_typo), true);
            if (array_key_exists($idCache, $listHH))
            {
                $listHH[$idCache]["new"] = $newHouseholdArray;
                file_put_contents($dir_file_not_typo, json_encode($listHH));
                return;
            }
        }
    }
}