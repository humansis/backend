<?php


namespace BeneficiaryBundle\Utils\DataTreatment;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Utils\BeneficiaryService;
use BeneficiaryBundle\Utils\DataVerifier\DuplicateVerifier;
use BeneficiaryBundle\Utils\HouseholdService;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializationContext;
use ProjectBundle\Entity\Project;
use Symfony\Component\DependencyInjection\Container;

class MissingTreatment extends AbstractTreatment
{

    /**
     * ET RETURN ONLY IF WE ADD THE NEW
     * @param Project $project
     * @param array $householdsArray
     * @param string $email
     * @return array
     */
    public function treat(Project $project, array $householdsArray, string $email)
    {
        foreach ($householdsArray as $index => $value) {
            $index = intval($index) + 6;
            if (!$value['household_locations'] || !$value['beneficiaries']) {
                return ['miss' => 'line ' . $index];
            }
            foreach ($value['beneficiaries'] as $beneficiary) {
                if (!$beneficiary['local_given_name'] || !$beneficiary['local_family_name'] || ($beneficiary['gender'] != 0 && $beneficiary['gender'] != 1) || ($beneficiary['status'] != '0' && $beneficiary['status'] != '1') || !$beneficiary['residency_status'] || !$beneficiary['date_of_birth']) {
                    return ['miss' => 'line ' . $index . ' (beneficiaries)'];
                }
            }
        }
        return $householdsArray;
    }
}
