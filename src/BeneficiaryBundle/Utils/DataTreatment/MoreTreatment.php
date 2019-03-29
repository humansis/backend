<?php


namespace BeneficiaryBundle\Utils\DataTreatment;

use BeneficiaryBundle\Entity\Household;
use ProjectBundle\Entity\Project;

class MoreTreatment extends AbstractTreatment
{

    /**
     * @param Project $project
     * @param array $householdsArray
     * @param string $email
     * @return array
     * @throws \RA\RequestValidatorBundle\RequestValidator\ValidationException
     * @throws \Exception
     */
    public function treat(Project $project, array $householdsArray, string $email)
    {
        foreach ($householdsArray as $householdArray) {
            $oldHousehold = $this->em->getRepository(Household::class)->find($householdArray['id_old']);
            if (!$oldHousehold instanceof Household) {
                continue;
            }
            foreach ($householdArray['data'] as $newBeneficiary) {
                $this->beneficiaryService->updateOrCreate($oldHousehold, $newBeneficiary, true);
            }
        }
        $listHouseholds = [];
        $this->getFromCache('mapping_new_old', $listHouseholds, $email);
        return $listHouseholds;
    }
}
