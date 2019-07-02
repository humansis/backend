<?php


namespace BeneficiaryBundle\Utils\DataTreatment;

use ProjectBundle\Entity\Project;

class MoreTreatment extends AbstractTreatment
{

    /**
     * Treat the typo issues
     * The frontend returns:
     * {
     *  errors:
     *     [
     *         {
     *             old: [],
     *             new: [],
     *             id_tmp_cache: int,
     *         }
     *     ]
     * }
     * @param Project $project
     * @param array $householdsArray
     * @param string $email
     * @return array
     * @throws \Exception
     */
    public function treat(Project $project, array &$householdsArray, string $email)
    {
        foreach ($householdsArray as $householdArray) {
            // Save to update the new household with its removed beneficiary
            $this->updateInCache($householdArray['id_tmp_cache'], $householdArray['new'], $email);
        }

        return $this->getFromCache('to_update', $email);
    }
}
