<?php


namespace DistributionBundle\Utils;


use BeneficiaryBundle\Form\HouseholdConstraints;
use DistributionBundle\Entity\Location;
use Doctrine\ORM\EntityManagerInterface;
use RA\RequestValidatorBundle\RequestValidator\RequestValidator;
use RA\RequestValidatorBundle\RequestValidator\ValidationException;

class LocationService
{

    /** @var EntityManagerInterface $em */
    private $em;

    /** @var RequestValidator $requestValidator */
    private $requestValidator;

    public function __construct(EntityManagerInterface $entityManager, RequestValidator $requestValidator)
    {
        $this->em = $entityManager;
        $this->requestValidator = $requestValidator;
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
}