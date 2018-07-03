<?php


namespace BeneficiaryBundle\Utils;


use BeneficiaryBundle\Entity\Household;
use DistributionBundle\Entity\Location;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\Serializer;
use RA\RequestValidatorBundle\RequestValidator\RequestValidator;
use BeneficiaryBundle\Form\HouseholdConstraints;
use RA\RequestValidatorBundle\RequestValidator\ValidationException;

class HouseholdService
{
    /** @var EntityManagerInterface $em */
    private $em;

    /** @var Serializer $serializer */
    private $serializer;

    /** @var BeneficiaryService $beneficiaryService */
    private $beneficiaryService;

    /** @var RequestValidator $requestValidator */
    private $requestValidator;


    public function __construct(
        EntityManagerInterface $entityManager,
        Serializer $serializer,
        BeneficiaryService $beneficiaryService,
        RequestValidator $requestValidator
    )
    {
        $this->em = $entityManager;
        $this->serializer = $serializer;
        $this->beneficiaryService = $beneficiaryService;
        $this->requestValidator = $requestValidator;
    }


    public function getAll(string $iso3, array $filters)
    {
        $households = $this->em->getRepository(Household::class)->getAllBy($iso3, $filters);
        return $households;
    }


    /**
     * @param array $householdArray
     * @return Household
     * @throws ValidationException
     * @throws \Exception
     */
    public function create(array $householdArray)
    {
        $this->requestValidator->validate(
            "household",
            HouseholdConstraints::class,
            $householdArray,
            'any'
        );

        /** @var Household $household */
        $household = new Household();
        $household->setNotes($householdArray["notes"])
            ->setLivelihood($householdArray["livelihood"])
            ->setLongitude($householdArray["longitude"])
            ->setLatitude($householdArray["latitude"])
            ->setAddressStreet($householdArray["address_street"])
            ->setAddressPostcode($householdArray["address_postcode"])
            ->setAddressNumber($householdArray["address_number"]);

        // Save or update location instance
        $location = $this->getOrSaveLocation($householdArray["location"]);
        $household->setLocation($location);

        $this->em->persist($household);

        if (!empty($householdArray["beneficiaries"]))
        {
            foreach ($householdArray["beneficiaries"] as $beneficiaryToSave)
            {
                $beneficiary = $this->beneficiaryService->updateOrCreate($household, $beneficiaryToSave, false);
                $this->em->persist($beneficiary);
            }
        }

        $this->em->flush();

        return $household;
    }

    /**
     * @param Household $household
     * @param array $householdArray
     * @return Household
     * @throws ValidationException
     * @throws \Exception
     */
    public function update(Household $household, array $householdArray)
    {
        $this->requestValidator->validate(
            "household",
            HouseholdConstraints::class,
            $householdArray,
            'any'
        );

        /** @var Household $household */
        $household = $this->em->getRepository(Household::class)->find($household);
        $household->setNotes($householdArray["notes"])
            ->setLivelihood($householdArray["livelihood"])
            ->setLongitude($householdArray["longitude"])
            ->setLatitude($householdArray["latitude"])
            ->setAddressStreet($householdArray["address_street"])
            ->setAddressPostcode($householdArray["address_postcode"])
            ->setAddressNumber($householdArray["address_number"]);

        // Save or update location instance
        $location = $this->getOrSaveLocation($householdArray["location"]);
        $household->setLocation($location);
        $this->em->persist($household);

        if (!empty($householdArray["beneficiaries"]))
        {
            foreach ($householdArray["beneficiaries"] as $beneficiaryToSave)
            {
                $beneficiary = $this->beneficiaryService->updateOrCreate($household, $beneficiaryToSave, false);
                $this->em->persist($beneficiary);
            }
        }
        $this->em->flush();

        return $household;
    }

    /**
     * @param array $locationArray
     * @return Location|null|object
     * @throws ValidationException
     */
    public function getOrSaveLocation(array $locationArray)
    {
        $this->requestValidator->validate(
            "location",
            HouseholdConstraints::class,
            $locationArray,
            'any'
        );

        $location = $this->em->getRepository(Location::class)->findOneBy([
            "countryIso3" => $locationArray["country_iso3"],
            "adm1" => $locationArray["adm1"],
            "adm2" => $locationArray["adm2"],
            "adm3" => $locationArray["adm3"],
            "adm4" => $locationArray["adm4"],
        ]);

        if (!$location instanceof Location)
        {
            $location = new Location();
            $location->setCountryIso3($locationArray["country_iso3"])
                ->setAdm1($locationArray["adm1"])
                ->setAdm2($locationArray["adm2"])
                ->setAdm3($locationArray["adm3"])
                ->setAdm4($locationArray["adm4"]);
            $this->em->persist($location);
        }

        return $location;
    }

    public function remove(Household $household)
    {
        $household->setArchived(true);
        $this->em->persist($household);
        $this->em->flush();

        return $household;
    }
}