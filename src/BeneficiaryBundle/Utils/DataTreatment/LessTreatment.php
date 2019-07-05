<?php


namespace BeneficiaryBundle\Utils\DataTreatment;

use ProjectBundle\Entity\Project;

class LessTreatment extends AbstractTreatment
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
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function treat(Project $project, array &$householdsArray, string $email)
    {
        foreach ($householdsArray as $householdArray) {
            // Save to update the new household with its removed beneficiary
            $this->updateInCache($householdArray['id_tmp_cache'], $householdArray['new'], $email);
        }
        
        $to_update = $this->getFromCache('to_update', $email);
        if (! $to_update) {
            $to_update = [];
        }
        $to_create = $this->getFromCache('to_create', $email);
        if (! $to_create) {
            $to_create = [];
        }

        // to preserve values with the same key
        return array_unique(array_merge($to_update, $to_create), SORT_REGULAR);
    }
}
