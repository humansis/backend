<?php


namespace BeneficiaryBundle\Utils;


use BeneficiaryBundle\Entity\CountrySpecific;
use Doctrine\ORM\EntityManagerInterface;

class CountrySpecificService
{
    /** @var EntityManagerInterface $em */
    private $em;


    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    public function getAll($countryIso3)
    {
        return $this->em->getRepository(CountrySpecific::class)->findBy(["countryIso3" => $countryIso3]);
    }

}