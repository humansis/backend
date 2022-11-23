<?php

declare(strict_types=1);

namespace Controller\OfflineApp;

use Doctrine\Persistence\ManagerRegistry;
use Entity\GeneralReliefItem;
use FOS\RestBundle\Controller\Annotations as Rest;
use InputType\GeneralReliefFilterInputType;
use InputType\GeneralReliefPatchInputType;
use Request\Pagination;
use Serializer\MapperInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class GeneralReliefItemController extends AbstractOfflineAppController
{
    public function __construct(private readonly ManagerRegistry $managerRegistry)
    {
    }
    /**
     * @Rest\Patch("/offline-app/v2/general-relief-items/{id}")
     *
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
     *
     */
    public function list(Request $request, GeneralReliefFilterInputType $filter, Pagination $pagination): JsonResponse
    {
        $countryIso3 = $request->headers->get('country');
        if (is_null($countryIso3)) {
            throw new BadRequestHttpException('Missing country header');
        }

        $list = $this->managerRegistry->getRepository(GeneralReliefItem::class)
            ->findByParams($filter, $pagination);

        $response = $this->json($list);
        $response->setEtag(md5($response->getContent()));
        $response->setPublic();
        $response->isNotModified($request);

        return $response;
    }
}
