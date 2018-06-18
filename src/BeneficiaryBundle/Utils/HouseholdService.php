<?php


namespace BeneficiaryBundle\Utils;


use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\Serializer;

class HouseholdService
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

    public function create($householdArray)
    {

    }

}