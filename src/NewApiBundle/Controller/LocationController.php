<?php

declare(strict_types=1);

namespace NewApiBundle\Controller;

use CommonBundle\Entity\Adm1;
use CommonBundle\Entity\Adm2;
use CommonBundle\Entity\Adm3;
use CommonBundle\Entity\Adm4;
use CommonBundle\Entity\Location;
use CommonBundle\Pagination\Paginator;
use CommonBundle\Repository\Adm1Repository;
use CommonBundle\Repository\Adm2Repository;
use CommonBundle\Repository\Adm3Repository;
use CommonBundle\Repository\Adm4Repository;
use CommonBundle\Repository\LocationRepository;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\Component\Country\Countries;
use NewApiBundle\Enum\RoleType;
use NewApiBundle\InputType\AdmFilterInputType;
use NewApiBundle\InputType\LocationFilterInputType;
use ProjectBundle\Repository\ProjectRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Component\HttpFoundation\JsonResponse;
use UserBundle\Entity\User;
use UserBundle\Entity\UserCountry;

/**
 * @Cache(expires="+5 days", public=true)
 */
class LocationController extends AbstractController
{
    /** @var Countries */
    private $countries;

    /**
     * @var LocationRepository
     */
    private $locationRepository;

    /**
     * @var Adm2Repository
     */
    private $adm2Repository;

    /**
     * @var Adm1Repository
     */
    private $adm1Repository;

    /**
     * @var Adm3Repository
     */
    private $adm3Repository;

    /**
     * @var Adm4Repository
     */
    private $adm4Repository;

    /**
     * @var ProjectRepository
     */
    private $projectRepository;

    /**
     * @var \NewApiBundle\Component\Http\Request
     */
    private $request;

    public function __construct(
        Countries $countries,
        LocationRepository $locationRepository,
        Adm1Repository $adm1Repository,
        Adm2Repository $adm2Repository,
        Adm3Repository $adm3Repository,
        Adm4Repository $adm4Repository,
        ProjectRepository $projectRepository,
        \NewApiBundle\Component\Http\Request $request
    )
    {
        $this->countries = $countries;
        $this->locationRepository = $locationRepository;
        $this->adm2Repository = $adm2Repository;
        $this->adm1Repository = $adm1Repository;
        $this->adm3Repository = $adm3Repository;
        $this->adm4Repository = $adm4Repository;
        $this->projectRepository = $projectRepository;
        $this->request = $request;
    }

    /**
     * @Rest\Get("/web-app/v1/countries/{iso3}")
     *
     * @param string $iso3
     *
     * @return JsonResponse
     */
    public function country(string $iso3): JsonResponse
    {
        $country = $this->countries->getCountry($iso3);
        if (null !== $country) {
            return $this->json($country);
        }

        throw $this->createNotFoundException();
    }

    /**
     * @Rest\Get("/web-app/v1/users/{id}/countries")
     *
     * @param User$user
     *
     * @return JsonResponse
     */
    public function userCountries(User $user): JsonResponse
    {
        $userRoles = $user->getRoles();
        $data = [];

        if (in_array(RoleType::ADMIN, $userRoles)) {

            return $this->json(new Paginator($this->countries->getAll()));
        } elseif (in_array(RoleType::COUNTRY_MANAGER, $userRoles) || in_array(RoleType::REGIONAL_MANAGER, $userRoles)) {

            /** @var UserCountry $userCountry */
            foreach ($user->getCountries() as $userCountry) {
                $country = $this->countries->getCountry($userCountry->getIso3());
                if ($country) {
                    $data[] = $country;
                }
            }
        } else {
            foreach($this->projectRepository->getProjectCountriesByUser($user) as $countryIso3){
                $country = $this->countries->getCountry($countryIso3['iso3']);
                if ($country) {
                    $data[] = $country;
                }
            }
        }

        return $this->json(new Paginator($data));
    }

    /**
     * @Rest\Get("/web-app/v1/countries")
     *
     * @return JsonResponse
     */
    public function countries(): JsonResponse
    {
        return $this->json(new Paginator($this->countries->getAll()));
    }

    /**
     * @Rest\Get("/web-app/v1/adm1/{id}")
     *
     * @param Adm1 $adm1
     *
     * @return JsonResponse
     */
    public function adm1(Adm1 $adm1): JsonResponse
    {
        return $this->json($adm1);
    }

    /**
     * @Rest\Get("/web-app/v1/adm2/{id}")
     *
     * @param Adm2 $adm2
     *
     * @return JsonResponse
     */
    public function adm2(Adm2 $adm2): JsonResponse
    {
        return $this->json($adm2);
    }

    /**
     * @Rest\Get("/web-app/v1/adm3/{id}")
     *
     * @param Adm3 $adm3
     *
     * @return JsonResponse
     */
    public function adm3(Adm3 $adm3): JsonResponse
    {
        return $this->json($adm3);
    }

    /**
     * @Rest\Get("/web-app/v1/adm4/{id}")
     *
     * @param Adm4 $adm4
     *
     * @return JsonResponse
     */
    public function adm4(Adm4 $adm4): JsonResponse
    {
        return $this->json($adm4);
    }

    /**
     * @Rest\Get("/web-app/v1/adm1")
     *
     * @param AdmFilterInputType $inputType
     *
     * @return JsonResponse
     */
    public function adm1List(AdmFilterInputType $inputType): JsonResponse
    {
        $country = $this->request->getCountry();
        $data = $this->adm1Repository->findByFilter($inputType, $country);

        return $this->json($data);
    }

    /**
     * @Rest\Get("/web-app/v1/adm1/{id}/adm2")
     *
     * @param Adm1 $adm1
     *
     * @return JsonResponse
     */
    public function adm2ListByAdm1(Adm1 $adm1): JsonResponse
    {
        $data = $this->adm2Repository->findByAdm1($adm1);

        return $this->json(new Paginator($data));
    }

    /**
     * @Rest\Get("/web-app/v1/adm2")
     *
     * @param AdmFilterInputType $inputType
     *
     * @return JsonResponse
     */
    public function adm2List(AdmFilterInputType $inputType): JsonResponse
    {
        $country = $this->request->getCountry();
        $data = $this->adm2Repository->findByFilter($inputType, $country);

        return $this->json($data);
    }

    /**
     * @Rest\Get("/web-app/v1/adm2/{id}/adm3")
     *
     * @param Adm2 $adm2
     *
     * @return JsonResponse
     */
    public function adm3ListByAdm2(Adm2 $adm2): JsonResponse
    {
        $data = $this->adm3Repository->findByAdm2($adm2);

        return $this->json(new Paginator($data));
    }

    /**
     * @Rest\Get("/web-app/v1/adm3")
     *
     * @param AdmFilterInputType $inputType
     *
     * @return JsonResponse
     */
    public function adm3List(AdmFilterInputType $inputType): JsonResponse
    {
        $country = $this->request->getCountry();
        $data = $this->adm3Repository->findByFilter($inputType, $country);

        return $this->json($data);
    }

    /**
     * @Rest\Get("/web-app/v1/adm3/{id}/adm4")
     *
     * @param Adm3 $adm3
     *
     * @return JsonResponse
     */
    public function adm4ListByAdm3(Adm3 $adm3): JsonResponse
    {
        $data = $this->adm4Repository->findByAdm3($adm3);

        return $this->json(new Paginator($data));
    }

    /**
     * @Rest\Get("/web-app/v1/adm4")
     *
     * @param AdmFilterInputType $inputType
     *
     * @return JsonResponse
     */
    public function adm4List(AdmFilterInputType $inputType): JsonResponse
    {
        $country = $this->request->getCountry();
        $data = $this->adm4Repository->findByFilter($inputType, $country);

        return $this->json($data);
    }

    /**
     * @Rest\Get("/web-app/v1/locations/{id}")
     *
     * @param Location $location
     *
     * @return JsonResponse
     */
    public function item(Location $location): JsonResponse
    {
        return $this->json($location);
    }

    /**
     * @Rest\Get("/web-app/v1/locations")
     *
     * @param LocationFilterInputType $filter
     *
     * @return JsonResponse
     */
    public function locations(LocationFilterInputType $filter): JsonResponse
    {
        $country = $this->request->getCountry();
        $locations = $this->locationRepository->findByParams($filter, $country);

        return $this->json($locations);
    }
}
