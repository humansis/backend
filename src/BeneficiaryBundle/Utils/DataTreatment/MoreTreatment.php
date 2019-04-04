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
     * @throws \Exception
     */
    public function treat(Project $project, array $householdsArray, string $email)
    {
        foreach ($householdsArray as $householdArray) {
            // Save to update the new household with its removed beneficiary
            $this->updateInCache($householdArray['id_tmp_cache'], $householdArray['new'], $email);
        }

        return $this->getFromCache('to_update', $email);
    }
}
