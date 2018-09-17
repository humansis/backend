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
use ProjectBundle\Entity\Project;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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
     */
    public function validateDistribution(DistributionData $distributionData)
    {
        $distributionData->setValidated(true);
        $this->em->persist($distributionData);
        $this->em->flush();

        return $distributionData;
    }

    /**
     * Create a distribution
     *
     * @param $countryISO3
     * @param array $distributionArray
     * @return array
     * @throws \Exception
     * @throws \RA\RequestValidatorBundle\RequestValidator\ValidationException
     */
    public function create($countryISO3, array $distributionArray)
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
            $criteria[] = $this->criteriaDistributionService->save($distribution, $item, false);
        }

        $this->em->persist($distribution);
        $this->em->flush();

        $name = $distribution->getName();
        $id = $distribution->getId();
        $distribution->setName($name.$id);

        $this->em->persist($distribution);

        $listReceivers = $this->guessBeneficiaries($projectTmp, $countryISO3, $distribution, $criteria);
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
     * @param Project $project
     * @param $countryISO3
     * @param DistributionData $distributionData
     * @param array $criteria
     * @return array
     */
    public function guessBeneficiaries(Project $project, $countryISO3, DistributionData $distributionData, array $criteria)
    {
        $criteriaArray = [];
        foreach ($criteria as $selectionCriterion)
        {
            $criteriaArray[] = $this->getArrayOfCriteria($selectionCriterion);
        }

        return $this->retriever->getReceivers(
            $project,
            $countryISO3,
            $this->guessTypeString($distributionData->getType()),
            $criteriaArray,
            $this->configurationLoader->load(['__country' => $countryISO3])
        );
    }

    /**
     * @param DistributionData $distributionData
     * @param array $listReceivers
     * @throws \Exception
     */
    public function saveReceivers(DistributionData $distributionData, array $listReceivers)
    {
        foreach ($listReceivers as $receiver)
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
     * Distribution Type change number to string
     * @param bool $type
     * @return string
     */
    public function guessTypeString(bool $type)
    {
        return ($type == 1) ? 'beneficiary' : 'household';
    }

    /**
     * Transform the object selectionCriteria to an array
     *
     * @param SelectionCriteria $selectionCriteria
     * @return array
     */
    public function getArrayOfCriteria(SelectionCriteria $selectionCriteria)
    {
        return [
            "table_string" => $selectionCriteria->getTableString(),
            "field_string" => $selectionCriteria->getFieldString(),
            "value_string" => $selectionCriteria->getValueString(),
            "condition_string" => $selectionCriteria->getConditionString(),
            "kind_beneficiary" => $selectionCriteria->getKindBeneficiary(),
            "id_field" => $selectionCriteria->getIdField()
        ];
    }

    /**
     * Get all distributions
     *
     * @return array
     */
    public function findAll()
    {
        return $this->em->getRepository(DistributionData::class)->findAll();
    }


    /**
     * Get all distributions
     *
     * @return array
     */
    public function findOneById(int $id)
    {
        return $this->em->getRepository(DistributionData::class)->findOneBy(['id' => $id]);
    }

    /**
     * Get all beneficiaries in a selected project
     *
     * @return array
     */
    public function getAllBeneficiariesInProject(Project $project)
    {
        return $this->em->getRepository(Beneficiary::class)->getAllOfProject($project->getId());
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
     * @param DistributionData $distributionData
     * @return null|object
     */
    public function archived(DistributionData $distributionData)
    {
        /** @var DistributionData $distribution */
        if (!empty($distributionData))
            $distributionData->setArchived(1);

        $this->em->persist($distributionData);
        $this->em->flush();

        return $distributionData;
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
}