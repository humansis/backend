<?php

namespace CommonBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use Swagger\Annotations as SWG;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class CountryController extends Controller
{
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
        foreach ($this->getParameter('app.countries') as $country) {
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
        return $this->json($this->getParameter('app.countries'));
    }
}
