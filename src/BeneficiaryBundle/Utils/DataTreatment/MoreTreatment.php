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
}