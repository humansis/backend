<?php


namespace CommonBundle\Utils;

use BeneficiaryBundle\Form\HouseholdConstraints;
use BeneficiaryBundle\InputType\LocationType;
use CommonBundle\Entity\Location;
use CommonBundle\InputType\Country;
use CommonBundle\Repository\LocationRepository;
use Doctrine\ORM\EntityManagerInterface;
use RA\RequestValidatorBundle\RequestValidator\RequestValidator;
use RA\RequestValidatorBundle\RequestValidator\ValidationException;

/**
 * Class LocationService
 * @package CommonBundle\Utils
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
     * @param $countryISO3
     * @param array $locationArray
     * @return Location|null|object
     * @throws ValidationException
     * @deprecated use getLocationByInputType
     */
    public function getLocation($countryISO3, array $locationArray)
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
     * @param Country $country
     * @param LocationType|null $locationType
     * @return Location|null
     */
    public function getLocationByInputType(Country $country, ?LocationType $locationType): ?Location
    {
        if (!$locationType) {
            return null;
        }
        
        for ($i = 4; $i > 0; $i--) {
            $locationId = $locationType->{'getAdm' . $i}();
            if ($locationId !== null) {
                return $this->locationRepository->find($locationId);
            }
        }

        return null;
    }
}
