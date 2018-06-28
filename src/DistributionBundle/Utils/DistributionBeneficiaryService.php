<?php

namespace DistributionBundle\Utils;

use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use DistributionBundle\Entity\DistributionData;
use BeneficiaryBundle\Entity\ProjectBeneficiary;
use DistributionBundle\Entity\DistributionBeneficiary;


class DistributionBeneficiaryService 
{
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
     * Create a distributionBeneficiary
     * 
     * @param array $distributionBeneficiaryArray
     * @return DistributionBeneficiary
     */
    public function create(array $distributionBeneficiaryArray) 
    {

        /** @var Distribution $distribution */
        $distributionBeneficiary = $this->serializer->deserialize(json_encode($distributionBeneficiaryArray), DistributionBeneficiary::class, 'json');
        $errors = $this->validator->validate($distributionBeneficiary);
        if (count($errors) > 0)
        {
            $errorsArray = [];
            foreach ($errors as $error)
            {
                $errorsArray[] = $error->getMessage();
            }
            throw new \Exception(json_encode($errorsArray), Response::HTTP_BAD_REQUEST);
        }
        $project = $distributionBeneficiary->getProjectBeneficiary();
        $projectTmp = $this->em->getRepository(ProjectBeneficiary::class)->find($project);
        if ($projectTmp instanceof ProjectBeneficiary)
            $distributionBeneficiary->setProjectBeneficiary($projectTmp);

        $distributionData = $distributionBeneficiary->getDistributionData();
        $distributionDataTmp = $this->em->getRepository(DistributionData::class)->find($distributionData);
        if ($distributionDataTmp instanceof DistributionData)
            $distributionBeneficiary->setDistributionData($distributionDataTmp);

        $this->em->persist($distributionBeneficiary);

        $this->em->flush();

        return $distributionBeneficiary;
    }

    /**
     * Get all distribution beneficiaries
     * 
     * @return array
     */
    public function findAll() 
    {
        return $this->em->getRepository(DistributionBeneficiary::class)->findAll();
    }

     /**
     * @param DistributionBeneficiary $distributionBeneficiary
     * @return bool
     */
    public function delete(DistributionBeneficiary $distributionBeneficiary)
    {
        $deleteDistributionBeneficiary = $this->em->getRepository(DistributionData::class)->find($distributionBeneficiary);

        try
        {
            $this->em->remove($deleteDistributionBeneficiary);
            $this->em->flush();
        }
        catch (\Exception $exception)
        {
            return false;
        }

        return true;
    }
    
}