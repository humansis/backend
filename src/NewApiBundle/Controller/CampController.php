<?php

namespace NewApiBundle\Controller;

use BeneficiaryBundle\Entity\Camp;
use CommonBundle\Pagination\Paginator;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class CampController extends AbstractController
{
    /**
     * @Rest\Get("/camps")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function camps(Request $request): JsonResponse
    {
        $countryIso3 = $request->headers->get('country', false);
        if (!$countryIso3) {
            throw new BadRequestHttpException('Missing country header');
        }

        $beneficiaries = $this->getDoctrine()->getRepository(Camp::class)->findByCountry($countryIso3);

        return $this->json(new Paginator($beneficiaries));
    }
}
