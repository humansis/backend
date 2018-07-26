<?php

namespace DistributionBundle\Utils;

use DistributionBundle\Utils\Retriever\DefaultRetriever;
use Doctrine\ORM\EntityManagerInterface;
use DoctrineExtensions\Query\Mysql\Date;
use JMS\Serializer\Serializer;
use DistributionBundle\Entity\DistributionData;
use DistributionBundle\Entity\Location;
use DistributionBundle\Entity\SelectionCriteria;
use ProjectBundle\Entity\Project;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Tests\Matcher\DumpedUrlMatcherTest;
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


    public function __construct(
        EntityManagerInterface $entityManager,
        Serializer $serializer,
        ValidatorInterface $validator,
        LocationService $locationService,
        CommodityService $commodityService,
        ConfigurationLoader $configurationLoader
    )
    {
        $this->em = $entityManager;
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->locationService = $locationService;
        $this->commodityService = $commodityService;
        $this->configurationLoader = $configurationLoader;
    }

    /**
     * Create a distribution
     *
     * @param array $distributionArray
     * @return DistributionData
     * @throws \Exception
     */
    public function create($countryISO3, array $distributionArray)
    {
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

        $location = $this->locationService->getOrSaveLocation($distributionArray['location']);
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

        $this->em->persist($distribution);
        $this->em->flush();

        $listReceivers = $this->guessBeneficiaries($countryISO3, $distribution);
        dump($listReceivers);

        return $distribution;
    }

    /**
     * @param $countryISO3
     * @param DistributionData $distributionData
     * @return mixed
     * @throws \Exception
     */
    public function guessBeneficiaries($countryISO3, DistributionData $distributionData)
    {
        $selectionCriteria = $distributionData->getSelectionCriteria();
        $defaultRetriever = new DefaultRetriever($this->em);
        $criteria = [];
        foreach ($selectionCriteria as $selectionCriterion)
        {
            $criteria[] = $this->getArrayOfCriteria($selectionCriterion);
        }
        return $defaultRetriever->getReceivers(
            $countryISO3,
            $distributionData->getType(),
            $criteria,
            $this->configurationLoader->load(['__country' => $countryISO3])
        );
    }

    public function guessTypeString(bool $type)
    {
        return ($type == 1)? 'beneficiary' : 'household';
    }

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
     * Archived a distribution
     *
     * @param DistributionData $distribution
     * @return DistributionData
     */
    public function archived(DistributionData $distribution)
    {
        /** @var DistributionData $distribution */
        $distributionData = $this->em->getRepository(DistributionData::class)->find($distribution);
        if (!empty($distributionData))
            $distribution->setArchived(1);

        $this->em->persist($distribution);
        $this->em->flush();

        return $distributionData;
    }


}