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

    public function create($countryIso3, array $countrySpecificArray)
    {
        $countrySpecific = new CountrySpecific();
        $countrySpecific->setType($countrySpecificArray["type"])
            ->setField($countrySpecificArray["field"])
            ->setCountryIso3($countryIso3);

        $this->em->persist($countrySpecific);
        $this->em->flush();

        return $countrySpecific;
    }

}