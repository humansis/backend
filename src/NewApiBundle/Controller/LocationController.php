<?php

declare(strict_types=1);

namespace NewApiBundle\Controller;

use CommonBundle\Entity\Location;
use CommonBundle\Pagination\Paginator;
use CommonBundle\Repository\LocationRepository;
use Doctrine\ORM\Tools\Pagination\Paginator as OrmPaginator;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\Component\Country\Countries;
use NewApiBundle\Enum\RoleType;
use NewApiBundle\InputType\LocationFilterInputType;
use ProjectBundle\Repository\ProjectRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
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
     * @var ProjectRepository
     */
    private $projectRepository;

    public function __construct(
        Countries $countries,
        LocationRepository $locationRepository,
        ProjectRepository $projectRepository
    )
    {
        $this->countries = $countries;
        $this->locationRepository = $locationRepository;
        $this->projectRepository = $projectRepository;
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
                $country = $this->countries->getCountry($userCountry->getCountryIso3());
                if ($country) {
                    $data[] = $country;
                }
            }
        } else {
            foreach($this->projectRepository->getProjectCountriesByUser($user) as $countryIso3){
                $country = $this->countries->getCountry($countryIso3['countryIso3']);
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
     * @param Location $adm1
     *
     * @return JsonResponse
     */
    public function adm1(Location $adm1): JsonResponse
    {
        return $this->json($adm1);
    }

    /**
     * @Rest\Get("/web-app/v1/adm2/{id}")
     *
     * @param Location $adm2
     *
     * @return JsonResponse
     */
    public function adm2(Location $adm2): JsonResponse
    {
        return $this->json($adm2);
    }

    /**
     * @Rest\Get("/web-app/v1/adm3/{id}")
     *
     * @param Location $adm3
     *
     * @return JsonResponse
     */
    public function adm3(Location $adm3): JsonResponse
    {
        return $this->json($adm3);
    }

    /**
     * @Rest\Get("/web-app/v1/adm4/{id}")
     *
     * @param Location $adm4
     *
     * @return JsonResponse
     */
    public function adm4(Location $adm4): JsonResponse
    {
        return $this->json($adm4);
    }

    /**
     * @Rest\Get("/web-app/v1/adm1")
     *
     * @param Request            $request
     * @param LocationFilterInputType $inputType
     *
     * @return JsonResponse
     */
    public function adm1List(Request $request, LocationFilterInputType $inputType): JsonResponse
    {
        $data = $this->getAdmList($request, $inputType, 1);
        
        return $this->json($data);
    }

    /**
     * @Rest\Get("/web-app/v1/adm1/{id}/adm2")
     *
     * @param Request $request
     * @param Location $location
     * @return JsonResponse
     */
    public function adm2ListByAdm1(Request $request, Location $location): JsonResponse
    {
        $inputType = new LocationFilterInputType();
        $data = $this->getAdmList($request, $inputType, 2, $location->getId());

        return $this->json($data);
    }

    /**
     * @Rest\Get("/web-app/v1/adm2")
     *
     * @param Request $request
     * @param LocationFilterInputType $inputType
     *
     * @return JsonResponse
     */
    public function adm2List(Request $request, LocationFilterInputType $inputType): JsonResponse
    {
        $data = $this->getAdmList($request, $inputType, 2);

        return $this->json($data);
    }

    /**
     * @Rest\Get("/web-app/v1/adm2/{id}/adm3")
     *
     * @param Request $request
     * @param Location $location
     * 
     * @return JsonResponse
     */
    public function adm3ListByAdm2(Request $request, Location $location): JsonResponse
    {
        $inputType = new LocationFilterInputType();
        $data = $this->getAdmList($request, $inputType, 3, $location->getId());

        return $this->json($data);
    }

    /**
     * @Rest\Get("/web-app/v1/adm3")
     *
     * @param Request $request
     * @param LocationFilterInputType $inputType
     *
     * @return JsonResponse
     */
    public function adm3List(Request $request, LocationFilterInputType $inputType): JsonResponse
    {
        $data = $this->getAdmList($request, $inputType, 3);

        return $this->json($data);
    }

    /**
     * @Rest\Get("/web-app/v1/adm3/{id}/adm4")
     *
     * @param Request $request
     * @param Location $location
     * 
     * @return JsonResponse
     */
    public function adm4ListByAdm3(Request $request, Location $location): JsonResponse
    {
        $inputType = new LocationFilterInputType();
        $data = $this->getAdmList($request, $inputType, 4, $location->getId());

        return $this->json($data);
    }

    /**
     * @Rest\Get("/web-app/v1/adm4")
     *
     * @param Request $request
     * @param LocationFilterInputType $inputType
     *
     * @return JsonResponse
     */
    public function adm4List(Request $request, LocationFilterInputType $inputType): JsonResponse
    {
        $data = $this->getAdmList($request, $inputType, 4);

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
     * @param Request                 $request
     * @param LocationFilterInputType $filter
     *
     * @return JsonResponse
     */
    public function locations(Request $request, LocationFilterInputType $filter): JsonResponse
    {
        $countryIso3 = $request->headers->get('country', false);
        if (!$countryIso3) {
            throw new BadRequestHttpException('Missing country header');
        }

        $locations = $this->locationRepository->findByParams($filter, $countryIso3);

        return $this->json($locations);
    }

    private function getAdmList(Request $request, LocationFilterInputType $inputType, int $level, $parent = null): OrmPaginator
    {
        $countryIso3 = $request->headers->get('country', false);
        if (!$countryIso3) {
            throw new BadRequestHttpException('Missing country header');
        }

        $inputType->setFilter(['level' => $level]);
        if ($parent) {
            $inputType->setFilter(['parent' => $parent]);
        }
        return $this->locationRepository->findByParams($inputType, $countryIso3);
    }
}
