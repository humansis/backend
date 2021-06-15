<?php

declare(strict_types=1);

namespace NewApiBundle\Controller;

use CommonBundle\Entity\Adm1;
use CommonBundle\Entity\Adm2;
use CommonBundle\Entity\Adm3;
use CommonBundle\Entity\Adm4;
use CommonBundle\Entity\Location;
use CommonBundle\Pagination\Paginator;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\Component\Country\Countries;
use NewApiBundle\InputType\AdmFilterInputType;
use NewApiBundle\InputType\LocationFilterInputType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use UserBundle\Entity\User;

/**
 * @Cache(expires="+5 days", public=true)
 */
class LocationController extends AbstractController
{
    /** @var Countries */
    private $countries;

    public function __construct(Countries $countries)
    {
        $this->countries = $countries;
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
    public function userCountries(User $user)
    {
        if (0 === $user->getCountries()->count()) {
            return $this->json(new Paginator($this->getParameter('app.countries')));
        }

        $data = [];

        /** @var \UserBundle\Entity\UserCountry $userCountry */
        foreach ($user->getCountries() as $userCountry) {
            foreach ($this->getParameter('app.countries') as $country) {
                if ($userCountry->getIso3() === $country['iso3']) {
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
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function adm1List(Request $request, AdmFilterInputType $inputType): JsonResponse
    {
        if ($inputType->hasIds()) {
            $data = $this->getDoctrine()->getRepository(Adm1::class)->findByFilter($inputType);
        } elseif ($request->headers->has('country')) {
            $data = $this->getDoctrine()->getRepository(Adm1::class)->findByCountry($request->headers->get('country'));
        } else {
            throw new BadRequestHttpException('Missing header attribute country');
        }

        return $this->json(new Paginator($data));
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
        $data = $this->getDoctrine()->getRepository(Adm2::class)->findByAdm1($adm1);

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
        $data = $this->getDoctrine()->getRepository(Adm2::class)->findByFilter($inputType);

        return $this->json(new Paginator($data));
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
        $data = $this->getDoctrine()->getRepository(Adm3::class)->findByAdm2($adm2);

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
        $data = $this->getDoctrine()->getRepository(Adm3::class)->findByFilter($inputType);

        return $this->json(new Paginator($data));
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
        $data = $this->getDoctrine()->getRepository(Adm4::class)->findByAdm3($adm3);

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
        $data = $this->getDoctrine()->getRepository(Adm4::class)->findByFilter($inputType);

        return $this->json(new Paginator($data));
    }

    /**
     * @Rest\Get("/web-app/v1/locations/{id}")
     *
     * @param Location $location
     *
     * @return JsonResponse
     */
    public function item(Location $location)
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
    public function locations(LocationFilterInputType $filter)
    {
        $locations = $this->getDoctrine()->getRepository(Location::class)->findByParams($filter);

        return $this->json($locations);
    }
}
