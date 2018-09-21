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
     * @return array
     * @throws \Exception
     */
    public function treat(Project $project, array $householdsArray)
    {
        foreach ($householdsArray as $value){
            if(!$value['address_street'] || !$value['address_number'] || !$value['address_postcode'] || !$value['location'] || !$value['beneficiaries']){
                return ['miss' => 'Incomplete line'];
            }
            foreach ($value['beneficiaries'] as $beneficiary){
                if(!$beneficiary['given_name'] || !$beneficiary['family_name'] || ($beneficiary['gender'] != '0' && $beneficiary['gender'] != '1') || ($beneficiary['status'] != '0' && $beneficiary['status'] != '1') || !$beneficiary['date_of_birth']){
                    return ['miss' => 'Incomplete line'];
                }
            }
        }
        return $householdsArray;
    }
}