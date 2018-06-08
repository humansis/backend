<?php

namespace BeneficiaryBundle\Utils;

use BeneficiaryBundle\Entity\Beneficiary;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\Serializer;

class BeneficiaryService
{
    /** @var EntityManagerInterface $em */
    private $em;

    /** @var Serializer $serializer */
    private $serializer;

    public function __construct(EntityManagerInterface $entityManager, Serializer $serializer)
    {
        $this->em = $entityManager;
        $this->serializer = $serializer;
    }

    public function create(array $beneficiaryArray)
    {
        /** @var Beneficiary $beneficiary */
        $beneficiary = $this->serializer->deserialize(json_encode($beneficiaryArray), Beneficiary::class, 'json');
        $beneficiary->setBeneficiaryProfile(null)
            ->setVulnerabilityCriteria(null);
        $this->em->persist($beneficiary);
        $this->em->flush();
        return $beneficiary;
    }

    public function update(Beneficiary $beneficiary, array $beneficiaryArray)
    {

    }
}