<?php

declare(strict_types=1);

namespace NewApiBundle\Controller;

use DistributionBundle\Entity\GeneralReliefItem;
use DistributionBundle\Utils\AssistanceService;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\InputType\GeneralReliefFilterInputType;
use NewApiBundle\Request\Pagination;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class GeneralReliefItemController extends AbstractController
{
    /** @var AssistanceService */
    private $assistanceService;

    /**
     * GeneralReliefItemController constructor.
     *
     * @param AssistanceService $assistanceService
     */
    public function __construct(AssistanceService $assistanceService)
    {
        $this->assistanceService = $assistanceService;
    }

    /**
     * @Rest\Get("/general-relief-items/{id}")
     *
     * @param GeneralReliefItem $object
     *
     * @return JsonResponse
     */
    public function item(GeneralReliefItem $object): JsonResponse
    {
        return $this->json($object);
    }

    /**
     * @Rest\Patch("/general-relief-items/{id}")
     *
     * @param Request           $request
     * @param GeneralReliefItem $object
     *
     * @return JsonResponse
     */
    public function patch(Request $request, GeneralReliefItem $object): JsonResponse
    {
        if ($request->request->get('distributed', false)) {
            $this->assistanceService->setGeneralReliefItemsAsDistributed([$object->getId()]);
        }

        if ($request->request->has('notes')) {
            $this->assistanceService->editGeneralReliefItemNotes($object->getId(),
                $request->request->get('editGeneralReliefItemNotes'));
        }

        return $this->json($object);
    }

    /**
     * @Rest\Get("/general-relief-items")
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

        return $this->json($list);
    }
}
