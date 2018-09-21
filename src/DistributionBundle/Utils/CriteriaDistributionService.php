<?php


namespace DistributionBundle\Utils;


use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\CountrySpecific;
use BeneficiaryBundle\Entity\CountrySpecificAnswer;
use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Entity\VulnerabilityCriterion;
use DistributionBundle\Entity\DistributionData;
use DistributionBundle\Entity\SelectionCriteria;
use Doctrine\ORM\EntityManagerInterface;
use ProjectBundle\Entity\Project;

class CriteriaDistributionService
{

    /** @var EntityManagerInterface $em */
    private $em;

    /** @var ConfigurationLoader $configurationLoader */
    private $configurationLoader;


    /**
     * CriteriaDistributionService constructor.
     * @param EntityManagerInterface $entityManager
     * @param ConfigurationLoader $configurationLoader
     * @throws \Exception
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        ConfigurationLoader $configurationLoader
    )
    {
        $this->em = $entityManager;
        $this->configurationLoader = $configurationLoader;
    }


    /**
     * @param array $filters
     * @param int $threshold
     * @return mixed
     */
    public function load(array $filters, int $threshold = 1)
    {
        $countryISO3 = $filters['__country'];
        $distributionType = $filters['distribution_type'];
        $finalArray = array();

        foreach ($filters['criteria'] as $criterion){

            if($criterion['kind_beneficiary'] == 'Household'){
                $finalArray = $this->kindHousehold($finalArray, $criterion, $distributionType, $countryISO3);
            }
            elseif($criterion['kind_beneficiary'] == 'Beneficiary'){
                $finalArray = $this->kindBeneficiary($finalArray, $criterion, $distributionType, $threshold);
            }
        }
      
        return ['number' => count($finalArray)];
    }

    /**
     * @param DistributionData $distributionData
     * @param SelectionCriteria $selectionCriteria
     * @param bool $flush
     * @return SelectionCriteria
     */
    public function save(DistributionData $distributionData, SelectionCriteria $selectionCriteria, bool $flush)
    {
        $selectionCriteria->setDistributionData($distributionData);
        $this->em->persist($selectionCriteria);
        if ($flush)
            $this->em->flush();
        return $selectionCriteria;
    }

    /**
     * @param array $filters
     * @return array
     */
    public function getAll(array $filters)
    {
        $criteria = $this->configurationLoader->load($filters);
        return $criteria;
    }

    /**
     * @param array $finalArray
     * @param array $criterion
     * @param string $distributionType
     * @param string $countryISO3
     * @return array
     */
    public function kindHousehold(array $finalArray, array $criterion, string $distributionType, string $countryISO3){
        $countrySpecific = $this->em->getRepository(CountrySpecific::class)->findBy(['fieldString' => $criterion['field_string'], 'countryIso3' => $countryISO3]);
        $countrySpecificAnswer = $this->em->getRepository(CountrySpecificAnswer::class)->findForValue($countrySpecific[0]->getId(), $criterion['value_string'], $criterion['condition_string']);

        $households = array();
        foreach ($countrySpecificAnswer as $countrySpecificRDO)
            array_push($households, $countrySpecificRDO->getHousehold());

        foreach ($households as $household) {
            //$count = 0;
            $beneficiaries = $this->em->getRepository(Beneficiary::class)->findByHousehold($household);
            /*foreach ($beneficiaries as $beneficiary)
                foreach ($beneficiary->getVulnerabilityCriteria()->getValues() as $value)
                    $count = $count + 1;*/
            //if($count >= 3){
            if($distributionType == "household"){
                if(!in_array($household, $finalArray))
                    array_push($finalArray, $household);
            }
            else{
                foreach ($beneficiaries as $beneficiary){
                    if(!in_array($beneficiary, $finalArray))
                        array_push($finalArray, $beneficiary);
                }
            }
            //}
        }

        return $finalArray;
    }

    /**
     * @param array $finalArray
     * @param array $criterion
     * @param string $distributionType
     * @param int $threshold
     * @return array
     */
    public function kindBeneficiary(array $finalArray, array $criterion, string $distributionType, int $threshold){
        $vulnerabilityCriteria = $this->em->getRepository(VulnerabilityCriterion::class)->findBy(['fieldString' => $criterion['field_string']]);

        if($criterion['field_string'] == "dateOfBirth"){
            $beneficiaries = $this->em->getRepository(Beneficiary::class)->findByDateOfBirth($criterion['value_string'], $criterion['condition_string']);
        }
        else{
            $beneficiaries = $this->em->getRepository(Beneficiary::class)->findByVulnerabilityCriterion($vulnerabilityCriteria[0]->getId(), $criterion['condition_string']);
        }

        foreach ($beneficiaries as $beneficiary){
            $count = 0;

            foreach ($beneficiary->getVulnerabilityCriteria()->getValues() as $value){
                if($value->getFieldString() == $criterion['field_string']){
                    $count = $count + 1;
                }
            }

            if($count >= $threshold){
                if($distributionType == "household"){
                    $household = $beneficiary->getHousehold();
                    if(!in_array($household, $finalArray))
                        array_push($finalArray, $household);
                }
                else{
                    if(!in_array($beneficiary, $finalArray))
                        array_push($finalArray, $beneficiary);
                }
            }
        }

        return $finalArray;
    }
}