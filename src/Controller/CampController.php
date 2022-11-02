<?php

namespace Controller;

use Entity\Camp;
use Entity\Location;
use Pagination\Paginator;
use FOS\RestBundle\Controller\Annotations as Rest;
use InputType\CampFilterInputType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class CampController extends AbstractController
{
    /**
     * @Rest\Get("/web-app/v1/camps")
     *
     *
     */
    public function camps(Request $request, CampFilterInputType $filterInputType): JsonResponse
    {
        $countryIso3 = $request->headers->get('country', false);
        if (!$countryIso3) {
            throw new BadRequestHttpException('Missing country header');
        }

        $beneficiaries = $this->getDoctrine()->getRepository(Camp::class)->findByCountry(
            $countryIso3,
            $filterInputType
        );

        return $this->json(new Paginator($beneficiaries));
    }

    /**
     * @Rest\Get("/web-app/v1/camps/{id}")
     *
     *
     */
    public function camp(Camp $camp): JsonResponse
    {
        return $this->json($camp);
    }

    /**
     * @Rest\Get("/web-app/v1/locations/{id}/camps")
     *
     *
     */
    public function campsByLocation(Location $location): JsonResponse
    {
        $camps = $this->getDoctrine()->getRepository(Camp::class)->findBy(['location' => $location]);

        return $this->json(new Paginator($camps));
    }
}
