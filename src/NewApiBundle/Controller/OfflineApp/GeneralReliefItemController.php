<?php

declare(strict_types=1);

namespace NewApiBundle\Controller\OfflineApp;

use DistributionBundle\Entity\GeneralReliefItem;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\InputType\GeneralReliefFilterInputType;
use NewApiBundle\InputType\GeneralReliefPatchInputType;
use NewApiBundle\Request\Pagination;
use NewApiBundle\Serializer\MapperInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class GeneralReliefItemController extends AbstractOfflineAppController
{
    /**
     * @Rest\Patch("/offline-app/v2/general-relief-items/{id}")
     *
     * @param GeneralReliefItem           $object
     * @param GeneralReliefPatchInputType $inputType
     *
     * @return JsonResponse
     */
    public function patch(GeneralReliefItem $object, GeneralReliefPatchInputType $inputType): Response
    {
        return new Response('Removed due Relief package migration', Response::HTTP_UPGRADE_REQUIRED);
    }

    /**
     * @Rest\Get("/offline-app/v1/general-relief-items")
     *
     * @param Request                      $request
     * @param GeneralReliefFilterInputType $filter
     * @param Pagination                   $pagination
     *
     * @return JsonResponse
     */
    public function list(Request $request, GeneralReliefFilterInputType $filter, Pagination $pagination): JsonResponse
    {
        $countryIso3 = $request->headers->get('country', false);
        if (!$countryIso3) {
            throw new BadRequestHttpException('Missing country header');
        }

        $list = $this->getDoctrine()->getRepository(GeneralReliefItem::class)
            ->findByParams($filter, $pagination);

        $response = $this->json($list);
        $response->setEtag(md5($response->getContent()));
        $response->setPublic();
        $response->isNotModified($request);

        return $response;
    }
}