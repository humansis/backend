<?php

declare(strict_types=1);

namespace NewApiBundle\Controller;

use CommonBundle\Pagination\Paginator;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\Component\Smartcard\SmartcardPurchaseService;
use NewApiBundle\InputType\SmartcardPurchaseFilterInputType;
use NewApiBundle\Request\Pagination;
use ProjectBundle\Entity\Project;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\JsonResponse;
use VoucherBundle\Entity\SmartcardPurchase;
use VoucherBundle\Entity\SmartcardRedemptionBatch;
use VoucherBundle\Entity\Vendor;

class SmartcardPurchaseController extends AbstractController
{
    /** @var SmartcardPurchaseService */
    private $smartcardPurchaseService;

    public function __construct(SmartcardPurchaseService $smartcardPurchaseService)
    {
        $this->smartcardPurchaseService = $smartcardPurchaseService;
    }

    /**
     * @Rest\Get("/smartcard-purchases")
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
     * @Rest\Get("/smartcard-redemption-batches/{id}/smartcard-purchases")
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

    /**
     * @Rest\Get("/vendor-app/v1/vendors/{vendorId}/projects/{projectId}/currencies/{currency}/smartcard-purchases")
     * @ParamConverter("vendor", options={"mapping": {"vendorId": "id"}})
     * @ParamConverter("project", options={"mapping": {"projectId" : "id"}})
     *
     * @param Vendor  $vendor
     * @param Project $project
     * @param string  $currency
     *
     * @return JsonResponse
     */
    public function purchasesByRedemptionBatchCandidate(Vendor $vendor, Project $project, string $currency): JsonResponse
    {
        $purchases = $this->smartcardPurchaseService->getBy($vendor, $project, $currency);

        return $this->json($purchases);
    }
}
