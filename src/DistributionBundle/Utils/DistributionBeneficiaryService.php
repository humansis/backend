<?php

namespace DistributionBundle\Utils;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Household;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\Serializer;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
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

    /** @var ContainerInterface $container */
    private $container;


    /**
     * DistributionBeneficiaryService constructor.
     * @param EntityManagerInterface $entityManager
     * @param Serializer $serializer
     * @param ValidatorInterface $validator
     * @param ContainerInterface $container
     */
    public function __construct(EntityManagerInterface $entityManager, Serializer $serializer, ValidatorInterface $validator, ContainerInterface $container)
    {
        $this->em = $entityManager;
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->container = $container;
    }
    
    /**
     * Get all beneficiaries from a distribution
     *
     * @param DistributionData $distributionData
     * @return array
     */
    public function getBeneficiaries(DistributionData $distributionData)
    {
        $beneficiaries = $this->em->getRepository(Beneficiary::class)->getAllofDistribution($distributionData);
        return $beneficiaries;
    }


    /**
     * Get random beneficiaries from a distribution
     *
     * @param DistributionData $distributionData
     * @param Int $numberRandomBeneficiary
     * @return array
     */
    public function getRandomBeneficiaries(DistributionData $distributionData, Int $numberRandomBeneficiary)
    {
        $listReceivers = $this->em->getRepository(Beneficiary::class)->getAllofDistribution($distributionData);
        if (sizeof($listReceivers) <= $numberRandomBeneficiary)
            return $listReceivers;
        
        $randomIds = array_rand($listReceivers, $numberRandomBeneficiary);
        $randomReceivers = array();
        foreach ($randomIds as $id) {
            array_push($randomReceivers, $listReceivers[$id]);
        }

        return $randomReceivers;
    }

    /**
     * Add either a beneficiary of a household(in this case, we assigned the head of the household) to a distribution
     *
     * @param DistributionData $distributionData
     * @param array $beneficiaryArray
     * @return DistributionBeneficiary
     * @throws \Exception
     */
    public function addBeneficiary(DistributionData $distributionData, array $beneficiaryArray)
    {
        $beneficiary = null;
        switch ($distributionData->getType())
        {
            case 0:
                $household = $this->em->getRepository(Household::class)->find($beneficiaryArray["id"]);
                if (!$household instanceof Household)
                    throw new \Exception("This household was not found.");
                $beneficiary = $this->em->getRepository(Beneficiary::class)->getHeadOfHousehold($household);
                break;
            case 1:
                $beneficiary = $this->em->getRepository(Beneficiary::class)->find($beneficiaryArray["id"]);
                break;
            default:
                throw new \Exception("The type of the distribution is undefined.");
        }

        $distributionBeneficiary = new DistributionBeneficiary();
        $distributionBeneficiary->setBeneficiary($beneficiary)
            ->setDistributionData($distributionData);

        $this->em->persist($distributionBeneficiary);

        $this->em->flush();

        return $distributionBeneficiary;
    }

    /**
     * @param DistributionBeneficiary $distributionBeneficiary
     * @return bool
     */
    public function remove(DistributionBeneficiary $distributionBeneficiary)
    {
        $this->em->remove($distributionBeneficiary);
        $this->em->flush();

        return true;
    }

    /**
     * @param Int $distributionId
     * @param Beneficiary $beneficiary
     * @return bool
     */
    public function removeBeneficiaryInDistribution(Int $distributionId, Beneficiary $beneficiary)
    {
        $distributionData = $this->em->getRepository(DistributionData::class)->find($distributionId);
        $distributionBeneficiary = $this->em->getRepository(DistributionBeneficiary::class)->findOneBy(['beneficiary' => $beneficiary->getId(), 'distributionData' => $distributionData->getId()]);
        
        $this->em->remove($distributionBeneficiary);
        $this->em->flush();
        return true;
    }

    /**
     * @param array $objectBeneficiary
     * @param string $type
     * @return mixed
     */
    public function exportToCsv(array $objectBeneficiary, string $type) {

        $beneficiaries = array();
        foreach ($objectBeneficiary as $value){
            array_push($beneficiaries, [
                "Given name" => $value->getGivenName(),
                "Family name"=> $value->getFamilyName(),
                "Gender" => $value->getGender(),
                "Status" => $value->getStatus(),
                "Date of birth" => $value->getDateOfBirth()->format('m/d/y')
            ]);
        }
        return $this->container->get('export_csv_service')->export($beneficiaries,'distributions', $type);
    }
}