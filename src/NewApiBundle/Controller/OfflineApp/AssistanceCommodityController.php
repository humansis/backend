<?php
declare(strict_types=1);

namespace NewApiBundle\Controller\OfflineApp;

use DistributionBundle\Entity\Commodity;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AssistanceCommodityController extends AbstractController
{
    /**
     * @Rest\Get("/offline-app/v2/commodities")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function commodities(Request $request): JsonResponse
    {
        $countryIso3 = $request->headers->get('country', false);
        if (!$countryIso3) {
            throw new BadRequestHttpException('Missing country header');
        }

        $commodities = $this->getDoctrine()->getRepository(Commodity::class)->findByCountry($countryIso3);

        return $this->json($commodities);
    }
}
