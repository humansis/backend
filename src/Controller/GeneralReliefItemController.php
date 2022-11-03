<?php

declare(strict_types=1);

namespace Controller;

use Doctrine\Persistence\ManagerRegistry;
use Entity\GeneralReliefItem;
use FOS\RestBundle\Controller\Annotations as Rest;
use InputType\GeneralReliefFilterInputType;
use InputType\GeneralReliefPatchInputType;
use Request\Pagination;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class GeneralReliefItemController extends AbstractController
{
    public function __construct(private readonly ManagerRegistry $managerRegistry)
    {
    }
    /**
     * @Rest\Get("/web-app/v1/general-relief-items/{id}")
     *
     *
     */
    public function item(GeneralReliefItem $object): JsonResponse
    {
        return $this->json($object);
    }

    /**
     * @Rest\Patch("/web-app/v2/general-relief-items/{id}")
     *
     *
     * @return JsonResponse
     */
    public function patchV2(GeneralReliefItem $object, GeneralReliefPatchInputType $inputType): Response
    {
        return new Response('Removed due Relief package migration', Response::HTTP_UPGRADE_REQUIRED);
    }

    /**
     * @Rest\Patch("/web-app/v1/general-relief-items/{id}")
     *
     *
     * @return JsonResponse
     * @deprecated Use self::patchV2() instead
     */
    public function patch(Request $request, GeneralReliefItem $object): Response
    {
        return new Response('Old endpoint', Response::HTTP_VERSION_NOT_SUPPORTED);
    }

    /**
     * @Rest\Get("/web-app/v1/general-relief-items")
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

        return $this->json($list);
    }
}