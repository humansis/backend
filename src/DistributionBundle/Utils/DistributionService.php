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


    public function __construct(EntityManagerInterface $entityManager, Serializer $serializer, ValidatorInterface $validator)
    {
        $this->em = $entityManager;
        $this->serializer = $serializer;
        $this->validator = $validator;
    }

    public function create(array $distributionArray) 
    {
        /** @var Distribution $distribution */
        dump($distributionArray);
        $distribution = $this->serializer->deserialize(json_encode($distributionArray), DistributionData::class, 'json');
        dump($distribution);

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
        dump($locationTmp);
        if ($locationTmp instanceof Location)
            $distribution->setLocation($locationTmp);

        $project = $distribution->getProject();
        $projectTmp = $this->em->getRepository(Project::class)->find($project);
        dump($projectTmp);
        if ($projectTmp instanceof Project)
            $distribution->setProject($projectTmp);

        $selectionCriteria = $distribution->getSelectionCriteria();
        $selectionCriteriaTmp = $this->em->getRepository(SelectionCriteria::class)->find($selectionCriteria);
        dump($selectionCriteriaTmp);
        if ($selectionCriteriaTmp instanceof SelectionCriteria)
            $distribution->setSelectionCriteria($selectionCriteriaTmp);

        $this->em->persist($distribution);

        $this->em->flush();

        return $distribution;
    }

    public function findAll() 
    {
        return $this->em->getRepository(DistributionData::class)->findAll();
    }
}