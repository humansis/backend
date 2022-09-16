<?php


namespace Utils;

use Form\HouseholdConstraints;
use InputType\Deprecated\LocationType;
use Entity\Location;
use Repository\LocationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use RA\RequestValidatorBundle\RequestValidator\RequestValidator;
use RA\RequestValidatorBundle\RequestValidator\ValidationException;

/**
 * Class LocationService
 * @package Utils
 */
class LocationService
{

    /** @var EntityManagerInterface $em */
    private $em;

    /** @var LocationRepository $locationRepository */
    private $locationRepository;

    /** @var RequestValidator $requestValidator */
    private $requestValidator;

    /**
     * LocationService constructor.
     * @param EntityManagerInterface $entityManager
     * @param LocationRepository $locationRepository
     * @param RequestValidator $requestValidator
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        LocationRepository $locationRepository,
        RequestValidator $requestValidator
    )
    {
        $this->em = $entityManager;
        $this->locationRepository = $locationRepository;
        $this->requestValidator = $requestValidator;
    }

    /**
     * @param int    $id
     * @param string $countryCode
     *
     * @return Location|object
     * @throws EntityNotFoundException
     */
    public function getLocationByIdAndCountryCode(int $id, string $countryCode)
    {
        $location = $this->locationRepository->findOneBy(['id' => $id, 'countryIso3' => $countryCode]);
        if (empty($location)) {
            throw new EntityNotFoundException("Location #{$id} was not found at country {$countryCode}.");
        }
        return $location;
    }

    /**
     * @param string $countryIso3
     * @param array  $locationArray
     *
     * @return Location|null|object
     * @throws ValidationException
     * @deprecated use getLocationByInputType
     */
    public function getLocation(string $countryIso3, array $locationArray)
    {
        $this->requestValidator->validate(
            "location",
            HouseholdConstraints::class,
            $locationArray,
            'any'
        );

        $location = null;
        for ($i = 4; $i > 0; $i--) {
            $admKey = 'adm' . $i;
            if ($locationArray[$admKey] === null) {
                continue;
            }

            $location = $this->locationRepository->find($locationArray[$admKey]);
            break;
        }

        return $location;
    }

    /**
     * @param LocationType|null $locationType
     * @return Location|null
     */
    public function getLocationByInputType(?LocationType $locationType): ?Location
    {
        if (!$locationType) {
            return null;
        }

        for ($i = 4; $i > 0; $i--) {
            $locationId = $locationType->getAdmByLevel($i);
            if ($locationId !== null) {
                return $this->locationRepository->find($locationId);
            }
        }

        return null;
    }
}
