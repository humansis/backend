<?php


namespace BeneficiaryBundle\Utils\DataTreatment;


use BeneficiaryBundle\Entity\Beneficiary;
use ProjectBundle\Entity\Project;

class LessTreatment extends AbstractTreatment
{

    /**
     * @param Project $project
     * @param array $householdsArray
     * @return array
     * @throws \Exception
     */
    public function treat(Project $project, array $householdsArray)
    {
        foreach ($householdsArray as $householdArray)
        {
            foreach ($householdArray['data'] as $oldBeneficiaryArray)
            {
                $oldBeneficiary = $this->em->getRepository(Beneficiary::class)->find($oldBeneficiaryArray['id']);
                if (!$oldBeneficiary instanceof Beneficiary)
                    continue;
                $this->beneficiaryService->remove($oldBeneficiary);
            }
        }
        $errors = $this->addHouseholds($project);
        return $errors;
    }

    /**
     * @param Project $project
     * @return array
     * @throws \Exception
     */
    public function addHouseholds(Project $project)
    {
        $householdsToAdd = $this->getHouseholdsNoTypo();
        $errors = [];
        foreach ($householdsToAdd as $householdToAdd)
        {
            try
            {
                $this->householdService->create($householdToAdd['new'], $project);
            }
            catch (\Exception $exception)
            {
                $errors[] = [
                    "household" => $householdToAdd,
                    "error" => "The creation of the household failed. Please check your CSV file."
                ];
            }
        }
        return $errors;
    }

    /**
     * @return mixed|null
     * @throws \Exception
     */
    private function getHouseholdsNoTypo()
    {
        if (null === $this->token)
            return null;

        $dir_root = $this->container->get('kernel')->getRootDir();
        $dir_var = $dir_root . '/../var/data/' . $this->token;
        if (!is_dir($dir_var))
            mkdir($dir_var);
        $dir_no_typo = $dir_var . '/no_typo';
        if (!is_file($dir_no_typo))
            return [];
        return json_decode(file_get_contents($dir_no_typo), true);
    }
}