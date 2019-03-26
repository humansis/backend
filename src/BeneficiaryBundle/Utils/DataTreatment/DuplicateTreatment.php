<?php


namespace BeneficiaryBundle\Utils\DataTreatment;


use BeneficiaryBundle\Entity\Beneficiary;
use ProjectBundle\Entity\Project;
use RA\RequestValidatorBundle\RequestValidator\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Cache\Simple\FilesystemCache;



class DuplicateTreatment extends AbstractTreatment
{

    /**
     * if state = 0 && there is the key "new" => delete the household saved (with the id 'old_id')
     * else, we keep it
     *
     * @param Project $project
     * @param array $householdsArray
     * @param string $email
     * @return array|Response
     * @throws ValidationException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \Exception
     */
    public function treat(Project $project, array $householdsArray, string $email)
    {
        $listHouseholds = [];
        $listHouseholdsFromTypo = [];
        $listHouseholdsFromNotTypo = [];
        $this->getFromCache('mapping_new_old', $listHouseholdsFromTypo, $email);
        $this->getFromCache('no_typo', $listHouseholdsFromNotTypo, $email);
        $this->clearCache('households.duplicate');
        foreach ($householdsArray as $householdData)
        {
            $newHousehold = $householdData['new_household'];
            foreach ($householdData['data'] as $beneficiaryData) {
                if (array_key_exists('new', $beneficiaryData)) {
                    if (intval($beneficiaryData['state']) === 0) {
                        $beneficiary = $this->em->getRepository(Beneficiary::class)->find($beneficiaryData['id_old']);
                        if (!$beneficiary instanceof Beneficiary)
                            throw new \Exception("Beneficiary not found.");
                        $deleted = $this->beneficiaryService->remove($beneficiary);
                        if (!$deleted)
                            throw new \Exception("This beneficiary is head of household. You can't delete her/him");
                    }

                    $household = $this->householdService->createOrEdit($newHousehold, array($project), null);

                    if (!$household) {
                        throw new \Exception("Unable to create a new household");
                    }

                    $this->saveHouseholds($email . '-households.duplicate', $household);

                    //UPDATE THE NEW HH IN THE CACHE
                    $this->updateInCache($householdData["id_tmp_cache"], $newHousehold, $email);
                } else {
                    $beneficiary = $this->em->getRepository(Beneficiary::class)->find($beneficiaryData['id_old']);
                    // we delete the beneficiary in the household which must be saved
                    foreach ($newHousehold['beneficiaries'] as $index => $newBeneficiary)
                    {
                        if ($newBeneficiary['given_name'] === $beneficiary->getGivenName()
                            && $newBeneficiary['family_name'] === $beneficiary->getFamilyName())
                        {
                            unset($newHousehold['beneficiaries'][$index]);
                            break;
                        }
                    }
                }
            }
        }
        $this->em->flush();
        $listHouseholdsFromCache = [];
        $this->getFromCache('mapping_new_old', $listHouseholdsFromCache, $email);

        return $listHouseholdsFromCache;
    }

    /**
     * @param $idCache
     * @param array $newHouseholdArray
     * @param string $email
     * @throws \Exception
     */
    private function updateInCache($idCache, array $newHouseholdArray, string $email)
    {
        if (null === $this->token)
            return;

        $dir_root = $this->container->get('kernel')->getRootDir();
        $dir_var = $dir_root . '/../var/data/' . $this->token;
        if (!is_dir($dir_var))
            mkdir($dir_var);

        $dir_file_mapping = $dir_var . '/' . $email . '-mapping_new_old';
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

        $dir_file_not_typo = $dir_var . '/' . $email . '-no_typo';
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