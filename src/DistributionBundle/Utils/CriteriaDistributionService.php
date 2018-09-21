<?php


namespace DistributionBundle\Utils;


use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\CountrySpecific;
use BeneficiaryBundle\Entity\CountrySpecificAnswer;
use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Entity\VulnerabilityCriterion;
use BeneficiaryBundle\Utils\Distribution\DefaultRetriever;
use DistributionBundle\Entity\DistributionData;
use DistributionBundle\Entity\SelectionCriteria;
use DistributionBundle\Utils\Retriever\AbstractRetriever;
use Doctrine\ORM\EntityManagerInterface;
use ProjectBundle\Entity\Project;

class CriteriaDistributionService
{

    /** @var EntityManagerInterface $em */
    private $em;

    /** @var ConfigurationLoader $configurationLoader */
    private $configurationLoader;

    /** @var AbstractRetriever $retriever */
    private $retriever;


    /**
     * CriteriaDistributionService constructor.
     * @param EntityManagerInterface $entityManager
     * @param ConfigurationLoader $configurationLoader
     * @param string $classRetrieverString
     * @throws \Exception
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        ConfigurationLoader $configurationLoader,
        string $classRetrieverString
    )
    {
        $this->em = $entityManager;
        $this->configurationLoader = $configurationLoader;
        try
        {
            $class = new \ReflectionClass($classRetrieverString);
            $this->retriever = $class->newInstanceArgs([$this->em]);
        }
        catch (\Exception $exception)
        {
            throw new \Exception("Your class Retriever is malformed.");
        }
    }


    /**
     * @param array $filters
     * @return mixed
     */
    public function load(array $filters)
    {
        $countryISO3 = $filters['__country'];
        $distributionType = $filters['distribution_type'];
        $finalArray = array();

        foreach ($filters['criteria'] as $criterion){

            if($criterion['kind_beneficiary'] == 'Household'){
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
            }
            elseif($criterion['kind_beneficiary'] == 'Beneficiary'){
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

                    if($count >= 1){
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
}