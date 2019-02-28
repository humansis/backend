<?php

namespace DistributionBundle\Utils;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Household;
use CommonBundle\Utils\LocationService;
use DistributionBundle\Entity\DistributionBeneficiary;
use DistributionBundle\Utils\Retriever\AbstractRetriever;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\Serializer;
use DistributionBundle\Entity\DistributionData;
use DistributionBundle\Entity\SelectionCriteria;
use DistributionBundle\Entity\GeneralReliefItem;
use ProjectBundle\Entity\Project;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class DistributionService
 * @package DistributionBundle\Utils
 */
class DistributionService
{

    /** @var EntityManagerInterface $em */
    private $em;

    /** @var Serializer $serializer */
    private $serializer;

    /** @var ValidatorInterface $validator */
    private $validator;

    /** @var LocationService $locationService */
    private $locationService;

    /** @var CommodityService $commodityService */
    private $commodityService;

    /** @var ConfigurationLoader $configurationLoader */
    private $configurationLoader;

    /** @var CriteriaDistributionService $criteriaDistributionService */
    private $criteriaDistributionService;

    /** @var AbstractRetriever $retriever */
    private $retriever;

    /** @var ContainerInterface $container */
    private $container;

    /**
     * DistributionService constructor.
     * @param EntityManagerInterface $entityManager
     * @param Serializer $serializer
     * @param ValidatorInterface $validator
     * @param LocationService $locationService
     * @param CommodityService $commodityService
     * @param ConfigurationLoader $configurationLoader
     * @param CriteriaDistributionService $criteriaDistributionService
     * @param string $classRetrieverString
     * @param ContainerInterface $container
     * @throws \Exception
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        Serializer $serializer,
        ValidatorInterface $validator,
        LocationService $locationService,
        CommodityService $commodityService,
        ConfigurationLoader $configurationLoader,
        CriteriaDistributionService $criteriaDistributionService,
        string $classRetrieverString,
        ContainerInterface $container
    )
    {
        $this->em = $entityManager;
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->locationService = $locationService;
        $this->commodityService = $commodityService;
        $this->configurationLoader = $configurationLoader;
        $this->criteriaDistributionService = $criteriaDistributionService;
        $this->container = $container;
        try

        {
            $class = new \ReflectionClass($classRetrieverString);
            $this->retriever = $class->newInstanceArgs([$this->em]);
        }
        catch (\Exception $exception)
        {
            throw new \Exception("Your class Retriever is undefined or malformed.");
        }
    }


    /**
     * @param DistributionData $distributionData
     * @return DistributionData
     * @throws \Exception
     */
    public function validateDistribution(DistributionData $distributionData)
    {
        try {
            $distributionData->setValidated(true);
            $commodities = $distributionData->getCommodities();
            foreach ($commodities as $commodity) {
                $modality = $commodity->getModalityType()->getModality();
                if ($modality->getName() === 'General Relief') {
                    $beneficiaries = $distributionData->getDistributionBeneficiaries();
                    foreach ($beneficiaries as $beneficiary) {
                        $generalRelief = new GeneralReliefItem();
                        $generalRelief->setDistributionBeneficiary($beneficiary);
                        $this->em->persist($generalRelief);
                    }
                }
            }

            $this->em->flush();
            return $distributionData;

        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Create a distribution
     *
     * @param $countryISO3
     * @param array $distributionArray
     * @param int $threshold
     * @return array
     * @throws \RA\RequestValidatorBundle\RequestValidator\ValidationException
     */
    public function create($countryISO3, array $distributionArray, int $threshold)
    {
        $location = $distributionArray['location'];
        unset($distributionArray['location']);
        /** @var DistributionData $distribution */
        $distribution = $this->serializer->deserialize(json_encode($distributionArray), DistributionData::class, 'json');
        $distribution->setUpdatedOn(new \DateTime());
        $errors = $this->validator->validate($distribution);
        if (count($errors) > 0)
        {
            $errorsArray = [];
            foreach ($errors as $error)
            {
                $errorsArray[] = $error->getMessage();
            }
            throw new \Exception(json_encode($errorsArray), Response::HTTP_BAD_REQUEST);
        }

        if($distributionArray['type'] === "Beneficiary") {
            $distribution->settype(1);
        } else {
            $distribution->settype(0);
        }

        $location = $this->locationService->getOrSaveLocation($countryISO3, $location);
        $distribution->setLocation($location);

        $project = $distribution->getProject();
        $projectTmp = $this->em->getRepository(Project::class)->find($project);
        if ($projectTmp instanceof Project)
            $distribution->setProject($projectTmp);


        foreach ($distribution->getCommodities() as $item)
        {
            $distribution->removeCommodity($item);
        }
        foreach ($distributionArray['commodities'] as $item)
        {
            $this->commodityService->create($distribution, $item, false);
        }
        $criteria = [];
        foreach ($distribution->getSelectionCriteria() as $item)
        {
            $distribution->removeSelectionCriterion($item);
            if($item->getTableString() == null)
                $item->setTableString("Beneficiary");

            $criteria[] = $this->criteriaDistributionService->save($distribution, $item, false);
        }

        $this->em->persist($distribution);
        $this->em->flush();

        $name = $distribution->getName();
        $distribution->setName($name);

        $this->em->persist($distribution);

        $listReceivers = $this->guessBeneficiaries($distributionArray, $countryISO3, $distributionArray['type'], $projectTmp, $threshold);
        $this->saveReceivers($distribution, $listReceivers);

        $this->em->flush();
        /** @var DistributionData $distribution */
        $distribution = $this->em->getRepository(DistributionData::class)
            ->find($distribution);
        $distributionBeneficiary = $this->em->getRepository(DistributionBeneficiary::class)
            ->findByDistributionData($distribution);
        $selectionsCriteria = $this->em->getRepository(SelectionCriteria::class)
            ->findByDistributionData($distribution);

        foreach ($distributionBeneficiary as $item)
        {
            $distribution->addDistributionBeneficiary($item);
        }
        foreach ($selectionsCriteria as $item)
        {
            $distribution->addSelectionCriterion($item);
        }

        return ["distribution" => $distribution, "data" => $listReceivers];
    }

    /**
     * @param array $criteria
     * @param $countryISO3
     * @param $type
     * @param Project $project
     * @param int $threshold
     * @return mixed
     */
    public function guessBeneficiaries(array $criteria, $countryISO3, $type, Project $project, int $threshold)
    {
        $criteria['criteria'] = $criteria['selection_criteria'];
        $criteria['countryIso3'] = $countryISO3;
        $criteria['distribution_type'] = $type;

        return $this->container->get('distribution.criteria_distribution_service')->load($criteria, $project, $threshold, false);
    }

    /**
     * @param DistributionData $distributionData
     * @param array $listReceivers
     * @throws \Exception
     */
    public function saveReceivers(DistributionData $distributionData, array $listReceivers)
    {
        foreach ($listReceivers['finalArray'] as $receiver)
        {
            if ($receiver instanceof Household)
            {
                $head = $this->em->getRepository(Beneficiary::class)->getHeadOfHousehold($receiver);
                $distributionBeneficiary = new DistributionBeneficiary();
                $distributionBeneficiary->setDistributionData($distributionData)
                    ->setBeneficiary($head);
            }
            elseif ($receiver instanceof Beneficiary)
            {
                $distributionBeneficiary = new DistributionBeneficiary();
                $distributionBeneficiary->setDistributionData($distributionData)
                    ->setBeneficiary($receiver);
            }
            else
            {
                throw new \Exception("A problem was found. The distribution has no beneficiary");
            }
            $this->em->persist($distributionBeneficiary);
        }
    }

    /**
     * Get all distributions
     *
     * @return array
     */
    public function findAll(string $country)
    {
        $distributions = [];
        $projects = $this->em->getRepository(Project::class)->findAll();
        
        foreach($projects as $proj) {
            if($proj->getIso3() == $country) {
                foreach($proj->getDistributions() as $distrib) {
                    array_push($distributions, $distrib);
                }
            }
        }

        return $distributions;
    }


    /**
     * Get all distributions
     *
     * @param int $id
     * @return null|object
     */
    public function findOneById(int $id)
    {
        return $this->em->getRepository(DistributionData::class)->findOneBy(['id' => $id]);
    }

    /**
     * @param DistributionData $distributionData
     * @return null|object|string
     */
    public function archived(DistributionData $distributionData)
    {
        if (!empty($distributionData))
            $distributionData->setArchived(1);

        $this->em->persist($distributionData);
        $this->em->flush();

        return "Archived";
    }

    /**
     * Edit a distribution
     *
     * @param DistributionData $distributionData
     * @param array $distributionArray
     * @return DistributionData
     * @throws \Exception
     */
    public function edit(DistributionData $distributionData, array $distributionArray)
    {
        /** @var DistributionData $distribution */
        $editedDistribution = $this->serializer->deserialize(json_encode($distributionArray), DistributionData::class, 'json');
        $editedDistribution->setId($distributionData->getId());

        $errors = $this->validator->validate($editedDistribution);
        if (count($errors) > 0)
        {
            $errorsArray = [];
            foreach ($errors as $error)
            {
                $errorsArray[] = $error->getMessage();
            }
            throw new \Exception(json_encode($errorsArray), Response::HTTP_BAD_REQUEST);
        }

        $this->em->merge($editedDistribution);
        $this->em->flush();

        return $editedDistribution;
    }


    /**
     * @param int $projectId
     * @param string $type
     * @return mixed
     */
    public function exportToCsv(int $projectId, string $type) {
        $exportableTable = $this->em->getRepository(DistributionData::class)->findBy(['project' => $projectId]);
        return $this->container->get('export_csv_service')->export($exportableTable,'distributions', $type);
    }

    /**
     * @param string $country
     * @return int
     */
    public function countAllBeneficiaries(string $country)
    {
        $count = (int) $this->em->getRepository(DistributionBeneficiary::class)->countAll($country);
        return $count;
    }
    
    /**
     * @param string $country
     * @return string
     */
    public function getTotalValue(string $country)
    {
        $value = (int) $this->em->getRepository(DistributionData::class)->getTotalValue($country);
        return $value;
    }

    /**
     * @param $distributions
     * @return string
     */
    public function filterDistributions($distributions)
    {
        $distributionArray = $distributions->getValues();
        $filteredArray = array();
        foreach ($distributionArray as $key) {
            if (!$key->getArchived()) {
                $filteredArray[] = $key;
            };
        }
        return $filteredArray;
    }

    /**
     * @param $country
     * @return string
     */
    public function getActiveDistributions($country)
    {
        $active = $this->em->getRepository(DistributionData::class)->getActiveByCountry($country);
        return $active;
    }
    
    /**
     * Initialise GRI for a distribution
     * @param  DistributionData $distributionData
     * @return void                           
     */
    public function createGeneralReliefItems(DistributionData $distributionData)
    {
        $distributionBeneficiaries = $distributionData->getDistributionBeneficiaries();
        foreach ($distributionBeneficiaries as $index => $distributionBeneficiary) {
            $$index = new GeneralReliefItem();
            $$index->setDistributionBeneficiary($distributionBeneficiary);
            $distributionBeneficiary->addGeneralRelief($$index);

            $this->em->persist($$index);
            $this->em->merge($distributionBeneficiary);
        }
        $this->em->flush();
    }

    /**
     * Edit notes of general relief item
     * @param  GeneralReliefItem $generalRelief
     * @param  string            $notes
     * @return DistributionBeneficiary
     */
    public function editGeneralReliefItemNotes(GeneralReliefItem $generalRelief, string $notes)
    {
        try {
            $generalRelief->setNotes($notes);
            $this->em->flush();
        } catch (\Exception $e) {
            throw new \Exception("Error updating general relief item");
        }

        $distributionBeneficiary = $this->em->getRepository(DistributionBeneficiary::class)->getByGRI($generalRelief);
        return $distributionBeneficiary;
    }

    /**
     * Set general relief items as distributed
     * @param array    $griIds
     * @param DateTime $distributedAt
     * @return array
     */
    public function setGeneralReliefItemsAsDistributed(array $griIds)
    {
        $errorArray = array();
        $successArray = array();

        foreach ($griIds as $griId) {
            $gri = $this->em->getRepository(GeneralReliefItem::class)->find($griId);

            if (!($gri instanceof GeneralReliefItem)) {
                array_push($errorArray, $griId);
            } else {
                $gri->setDistributedAt(new \DateTime());
                $this->em->merge($gri);
                array_push($successArray, $gri);
            }
        }

        $this->em->flush();

        return array($errorArray, $successArray);
    }
}