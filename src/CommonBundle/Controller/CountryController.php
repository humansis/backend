<?php

namespace CommonBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use Swagger\Annotations as SWG;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CountryController extends Controller
{
    // todo move to DB
    const COUNTRIES = [
        'KHM' => [
            'name' => 'Cambodia',
            'iso3' => 'KHM',
            'currency' => 'KHR',
        ],
        'SYR' => [
            'name' => 'Syria',
            'iso3' => 'SYR',
            'currency' => 'SYP',
        ],
        'UKR' => [
            'name' => 'Ukraine',
            'iso3' => 'UKR',
            'currency' => 'UAH',
        ],
        'ETH' => [
            'name' => 'Ethiopia',
            'iso3' => 'ETH',
            'currency' => 'ETB',
        ],
        'MNG' => [
            'name' => 'Mongolia',
            'iso3' => 'MNG',
            'currency' => 'MNT',
        ],
        'ARM' => [
            'name' => 'Armenia',
            'iso3' => 'ARM',
            'currency' => 'AMD',
        ],
    ];

    /**
     * @Rest\Get("/countries/{iso3}")
     *
     * @SWG\Tag(name="Location")
     *
     * @SWG\Response(
     *     response=200,
     *     description="All Available countries"
     * )
     *
     * @return Response
     */
    public function country(string $iso3)
    {
        foreach (self::COUNTRIES as $country) {
            if ($iso3 === $country['iso3']) {
                return $this->json($country);
            }
        }

        throw $this->createNotFoundException();
    }

    /**
     * @Rest\Get("/countries")
     *
     * @SWG\Tag(name="Location")
     *
     * @SWG\Response(
     *     response=200,
     *     description="All Available countries"
     * )
     *
     * @return Response
     */
    public function list()
    {
        return $this->json(array_values(self::COUNTRIES));
    }
}
