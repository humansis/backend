<?php


namespace BeneficiaryBundle\Utils\DataTreatment;


use BeneficiaryBundle\Entity\Household;
use ProjectBundle\Entity\Project;

class MoreTreatment extends AbstractTreatment
{

    /**
     * @param Project $project
     * @param array $householdsArray
     * @return array
     * @throws \Exception
     * @throws \RA\RequestValidatorBundle\RequestValidator\ValidationException
     */
    public function treat(Project $project, array $householdsArray)
    {
        foreach ($householdsArray as $householdArray)
        {
            $oldHousehold = $this->em->getRepository(Household::class)->find($householdArray['id_old']);
            if (!$oldHousehold instanceof Household)
                continue;
            foreach ($householdArray['data'] as $newBeneficiary)
            {
                $this->beneficiaryService->updateOrCreate($oldHousehold, $newBeneficiary, true);
            }
        }
        $listHouseholds = [];
        $this->getFromCache('mapping_new_old', $listHouseholds);
        return $listHouseholds;
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
}