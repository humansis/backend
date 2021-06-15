<?php

declare(strict_types=1);

namespace NewApiBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\InputType\SmartcardPurchaseFilterInputType;
use NewApiBundle\Request\Pagination;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\JsonResponse;
use VoucherBundle\Entity\SmartcardPurchase;
use VoucherBundle\Entity\SmartcardRedemptionBatch;

class SmartcardPurchaseController extends AbstractController
{
    /**
     * @Rest\Get("/web-app/v1/smartcard-purchases")
     * @Rest\Get("/vendor-app/v2/smartcard-purchases")
     *
     * @param SmartcardPurchaseFilterInputType $filter
     * @param Pagination                       $pagination
     *
     * @return JsonResponse
     */
    public function purchases(SmartcardPurchaseFilterInputType $filter, Pagination $pagination): JsonResponse
    {
        $purchases = $this->getDoctrine()->getRepository(SmartcardPurchase::class)
            ->findByParams($filter, $pagination);

        return $this->json($purchases);
    }

    /**
     * @Rest\Get("/web-app/v1/smartcard-redemption-batches/{id}/smartcard-purchases")
     * @ParamConverter("redemptionBatch", class="VoucherBundle\Entity\SmartcardRedemptionBatch")
     *
     * @param SmartcardRedemptionBatch $redemptionBatch
     * @param Pagination               $pagination
     *
     * @return JsonResponse
     */
    public function purchasesByRedemptionBatch(SmartcardRedemptionBatch $redemptionBatch, Pagination $pagination): JsonResponse
    {
        $purchases = $this->getDoctrine()->getRepository(SmartcardPurchase::class)
            ->findByBatch($redemptionBatch, $pagination);

        return $this->json($purchases);
    }
}
