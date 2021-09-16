<?php
declare(strict_types=1);

namespace NewApiBundle\Controller\OfflineApp;

use DistributionBundle\Entity\Commodity;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\Controller\AbstractController;
use NewApiBundle\InputType\CommodityOfflineFilterInputType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AssistanceCommodityController extends AbstractController
{
    /**
     * @Rest\Get("/offline-app/v2/commodities")
     *
     * @param Request                         $request
     * @param CommodityOfflineFilterInputType $filter
     *
     * @return JsonResponse
     */
    public function commodities(Request $request, CommodityOfflineFilterInputType $filter): JsonResponse
    {
        $countryIso3 = $request->headers->get('country', false);
        if (!$countryIso3) {
            throw new BadRequestHttpException('Missing country header');
        }

        $commodities = $this->getDoctrine()->getRepository(Commodity::class)->findOfflineByParams($countryIso3, $filter);

        $response = $this->json($commodities->getQuery()->getResult());
        $response->setEtag(md5($response->getContent()));
        $response->setPublic();
        $response->isNotModified($request);

        return $response;
    }
}
