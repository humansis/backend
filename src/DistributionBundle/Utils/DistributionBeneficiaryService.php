<?php

namespace DistributionBundle\Utils;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Household;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\Serializer;
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


    public function __construct(EntityManagerInterface $entityManager, Serializer $serializer, ValidatorInterface $validator)
    {
        $this->em = $entityManager;
        $this->serializer = $serializer;
        $this->validator = $validator;
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
    
}