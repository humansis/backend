<?php


namespace BeneficiaryBundle\Utils\DataTreatment;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Utils\BeneficiaryService;
use BeneficiaryBundle\Utils\HouseholdService;
use Doctrine\ORM\EntityManagerInterface;
use ProjectBundle\Entity\Project;
use Symfony\Component\DependencyInjection\Container;

class LessTreatment extends AbstractTreatment
{
    /**
     * @param Project $project
     * @param array $householdsArray
     * @param string $email
     * @return array
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function treat(Project $project, array $householdsArray, string $email)
    {
        dump($householdsArray);
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
