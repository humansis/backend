<?php

namespace DistributionBundle\Utils;

use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\Serializer;
use DistributionBundle\Entity\DistributionData;
use DistributionBundle\Entity\Location;
use DistributionBundle\Entity\SelectionCriteria;
use ProjectBundle\Entity\Project;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class DistributionService {

    /** @var EntityManagerInterface $em */
    private $em;

    /** @var Serializer $serializer */
    private $serializer;

    /** @var ValidatorInterface $validator */
    private $validator;


    public function __construct(EntityManagerInterface $entityManager, Serializer $serializer, ValidatorInterface $validator)
    {
        $this->em = $entityManager;
        $this->serializer = $serializer;
        $this->validator = $validator;
    }

    /**
     * Create a distribution
     * 
     * @param array $distributionArray
     * @return DistributionData
     */
    public function create(array $distributionArray) 
    {
        /** @var Distribution $distribution */
        $distribution = $this->serializer->deserialize(json_encode($distributionArray), DistributionData::class, 'json');

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
        
        $location = $distribution->getLocation();
        $locationTmp = $this->em->getRepository(Location::class)->find($location);
        if ($locationTmp instanceof Location)
            $distribution->setLocation($locationTmp);

        $project = $distribution->getProject();
        $projectTmp = $this->em->getRepository(Project::class)->find($project);
        if ($projectTmp instanceof Project)
            $distribution->setProject($projectTmp);

        $selectionCriteria = $distribution->getSelectionCriteria();
        $selectionCriteriaTmp = $this->em->getRepository(SelectionCriteria::class)->find($selectionCriteria);
        if ($selectionCriteriaTmp instanceof SelectionCriteria)
            $distribution->setSelectionCriteria($selectionCriteriaTmp);

        $this->em->persist($distribution);

        $this->em->flush();

        return $distribution;
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
        /** @var Distribution $distribution */
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
     * @param DistributionData $distributionData
     * @return bool
    */
    public function archived(DistributionData $distribution)
    {
        /** @var Distribution $distribution */
        $distributionData = $this->em->getRepository(DistributionData::class)->findById($distribution->getId());
        if (!empty($distributionData))
            $distribution->setArchived(1);

        $this->em->persist($distribution);
        $this->em->flush();

        return true;
    }

    
}