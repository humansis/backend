<?php

declare(strict_types=1);

namespace Controller\OfflineApp;

use Doctrine\Persistence\ManagerRegistry;
use Entity\Commodity;
use FOS\RestBundle\Controller\Annotations as Rest;
use Controller\AbstractController;
use InputType\CommodityOfflineFilterInputType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AssistanceCommodityController extends AbstractController
{
    public function __construct(private readonly ManagerRegistry $managerRegistry)
    {
    }
    /**
     * @Rest\Get("/offline-app/v2/commodities")
     *
     *
     */
    public function commodities(Request $request, CommodityOfflineFilterInputType $filter): JsonResponse
    {
        $countryIso3 = $request->headers->get('country');
        if (is_null($countryIso3)) {
            throw new BadRequestHttpException('Missing country header');
        }

        $commodities = $this->managerRegistry->getRepository(Commodity::class)->findOfflineByParams(
            $countryIso3,
            $filter
        );

        $response = $this->json($commodities->getQuery()->getResult());
        $response->setEtag(md5($response->getContent()));
        $response->setPublic();
        $response->isNotModified($request);

        return $response;
    }
}
