<?php

declare(strict_types=1);

namespace NewApiBundle\Controller;

use BeneficiaryBundle\Entity\Beneficiary;
use CommonBundle\Pagination\Paginator;
use DistributionBundle\Entity\Assistance;
use DistributionBundle\Entity\GeneralReliefItem;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\InputType\GeneralReliefFilterInputType;
use NewApiBundle\Request\Pagination;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
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

    /**
     * @Rest\Get("/assistances/{assistanceId}/beneficiaries/{beneficiaryId}/general-relief-items")
     * @ParamConverter("assistance", options={"mapping": {"assistanceId" : "id"}})
     * @ParamConverter("beneficiary", options={"mapping": {"beneficiaryId" : "id"}})
     *
     * @param Assistance  $assistance
     * @param Beneficiary $beneficiary
     *
     * @return JsonResponse
     */
    public function byAssistanceAndBeneficiary(Assistance $assistance, Beneficiary $beneficiary): JsonResponse
    {
        $list = $this->getDoctrine()->getRepository(GeneralReliefItem::class)
            ->findByAssistanceBeneficiary($assistance, $beneficiary);

        return $this->json(new Paginator($list));
    }
}
