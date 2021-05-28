<?php

declare(strict_types=1);

namespace NewApiBundle\Controller;

use DistributionBundle\Entity\GeneralReliefItem;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\InputType\GeneralReliefFilterInputType;
use NewApiBundle\InputType\GeneralReliefPatchInputType;
use NewApiBundle\Request\Pagination;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class GeneralReliefItemController extends AbstractController
{
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
     * @Rest\Patch("/offline-app/v2/general-relief-items/{id}")
     *
     * @param GeneralReliefItem           $object
     * @param GeneralReliefPatchInputType $inputType
     *
     * @return JsonResponse
     */
    public function patchOfflineApp(GeneralReliefItem $object, GeneralReliefPatchInputType $inputType): JsonResponse
    {
        $this->get('distribution.assistance_service')->patchGeneralReliefItem($object, $inputType);

        return $this->json($object);
    }

    /**
     * @Rest\Patch("/web-app/v2/general-relief-items/{id}")
     *
     * @param GeneralReliefItem           $object
     * @param GeneralReliefPatchInputType $inputType
     *
     * @return JsonResponse
     */
    public function patchV2(GeneralReliefItem $object, GeneralReliefPatchInputType $inputType): JsonResponse
    {
        $this->get('distribution.assistance_service')->patchGeneralReliefItem($object, $inputType);

        return $this->json($object);
    }

    /**
     * @Rest\Patch("/general-relief-items/{id}")
     *
     * @param Request           $request
     * @param GeneralReliefItem $object
     *
     * @return JsonResponse
     * @deprecated Use self::patchV2() instead
     */
    public function patch(Request $request, GeneralReliefItem $object): JsonResponse
    {
        if ($request->request->get('distributed', false)) {
            $this->get('distribution.assistance_service')->setGeneralReliefItemsAsDistributed([$object->getId()]);
        }

        if ($request->request->has('notes')) {
            $this->get('distribution.assistance_service')->editGeneralReliefItemNotes($object->getId(),
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
