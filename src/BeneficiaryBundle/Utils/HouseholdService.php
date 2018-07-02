<?php


namespace BeneficiaryBundle\Utils;


use BeneficiaryBundle\Entity\Household;
use DistributionBundle\Entity\Location;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\Serializer;

class HouseholdService
{
    /** @var EntityManagerInterface $em */
    private $em;

    /** @var Serializer $serializer */
    private $serializer;

    /** @var BeneficiaryService $beneficiaryService */
    private $beneficiaryService;


    public function __construct(EntityManagerInterface $entityManager, Serializer $serializer, BeneficiaryService $beneficiaryService)
    {
        $this->em = $entityManager;
        $this->serializer = $serializer;
        $this->beneficiaryService = $beneficiaryService;
    }


    public function getAll(string $iso3, array $filters)
    {
        $households = $this->em->getRepository(Household::class)->getAllBy($iso3, $filters);
        return $households;
    }


    /**
     * @param $householdArray
     * @return Household
     * @throws \Exception
     */
    public function create($householdArray)
    {
        /** @var Household $householdDeserialized */
        $householdDeserialized = $this->serializer->deserialize(json_encode($householdArray), Household::class, 'json');

        $locationToSaved = $householdDeserialized->getLocation();
        $householdDeserialized->setLocation($this->getOrSaveLocation($locationToSaved));

        $beneficiaries = $householdDeserialized->getBeneficiaries();
        $householdDeserialized->setBeneficiaries(null);
        $this->em->persist($householdDeserialized);
        if (!empty($beneficiaries))
        {
            foreach ($beneficiaries as $beneficiaryToSave)
            {
                $this->beneficiaryService->create($householdDeserialized, $beneficiaryToSave, false);
            }
        }

        $this->em->flush();
        return $householdDeserialized;
    }

    // TODO FINISH THIS FUNCTION
    public function update(Household $household, array $householdArray)
    {
        dump($householdArray);
        /** @var Household $householdDeserialized */
        $householdDeserialized = $this->serializer
            ->deserialize(json_encode($householdArray), Household::class, 'json');

        $householdDeserialized->setId($household->getId());

        $locationToSaved = $householdDeserialized->getLocation();
        $householdDeserialized->setLocation($this->getOrSaveLocation($locationToSaved));

        $beneficiaries = $householdDeserialized->getBeneficiaries();
        $householdDeserialized->setBeneficiaries(null);

        if (!empty($beneficiaries))
        {
            foreach ($beneficiaries as $beneficiaryToSave)
            {
                $beneficiary = $this->beneficiaryService->create($householdDeserialized, $beneficiaryToSave, false);
                $this->em->persist($beneficiary);
            }
        }

        $this->em->merge($householdDeserialized);
        $this->em->flush();
        return $householdDeserialized;
    }

    public function getOrSaveLocation(Location $locationToSaved)
    {
        $location = $this->em->getRepository(Location::class)->findOneBy([
            "countryIso3" => $locationToSaved->getCountryIso3(),
            "adm1" => $locationToSaved->getAdm1(),
            "adm2" => $locationToSaved->getAdm2(),
            "adm3" => $locationToSaved->getAdm3(),
            "adm4" => $locationToSaved->getAdm4(),
        ]);

        if (!$location instanceof Location)
        {
            $location = new Location();
            $location->setCountryIso3($locationToSaved->getCountryIso3())
                ->setAdm1($locationToSaved->getAdm1())
                ->setAdm2($locationToSaved->getAdm2())
                ->setAdm3($locationToSaved->getAdm3())
                ->setAdm4($locationToSaved->getAdm4());
            $this->em->persist($location);
        }

        return $location;
    }
}