<?php

declare(strict_types=1);

namespace Controller\WebApp\Smartcard;

use FOS\RestBundle\Controller\Annotations as Rest;
use Component\Smartcard\SmartcardPurchaseService;
use Controller\WebApp\AbstractWebAppController;
use InputType\SmartcardPurchaseFilterInputType;
use Request\Pagination;
use Entity\Project;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Entity\Invoice;
use Entity\Vendor;
use Repository\SmartcardPurchaseRepository;

class SmartcardPurchaseController extends AbstractWebAppController
{
    /** @var SmartcardPurchaseService */
    private $smartcardPurchaseService;

    public function __construct(SmartcardPurchaseService $smartcardPurchaseService)
    {
        $this->smartcardPurchaseService = $smartcardPurchaseService;
    }

    /**
     * @Rest\Get("/web-app/v1/smartcard-purchases")
     * @Rest\Get("/vendor-app/v2/smartcard-purchases")
     *
     * @param SmartcardPurchaseFilterInputType $filter
     * @param Pagination $pagination
     * @param SmartcardPurchaseRepository $smartcardPurchaseRepository
     *
     * @return JsonResponse
     */
    public function purchases(
        SmartcardPurchaseFilterInputType $filter,
        Pagination $pagination,
        SmartcardPurchaseRepository $smartcardPurchaseRepository
    ): JsonResponse {
        $purchases = $smartcardPurchaseRepository->findByParams($filter, $pagination);

        return $this->json($purchases);
    }

    /**
     * @Rest\Get("/web-app/v1/smartcard-redemption-batches/{id}/smartcard-purchases")
     * @ParamConverter("redemptionBatch", class="Entity\Invoice")
     *
     * @param Invoice $redemptionBatch
     * @param Pagination $pagination
     * @param SmartcardPurchaseRepository $smartcardPurchaseRepository
     *
     * @return JsonResponse
     */
    public function purchasesByRedemptionBatch(
        Invoice $redemptionBatch,
        Pagination $pagination,
        SmartcardPurchaseRepository $smartcardPurchaseRepository
    ): JsonResponse {
        $purchases = $smartcardPurchaseRepository->findByBatch($redemptionBatch, $pagination);

        return $this->json($purchases);
    }

    /**
     * @Rest\Get("/vendor-app/v1/vendors/{vendorId}/projects/{projectId}/currencies/{currency}/smartcard-purchases")
     * @ParamConverter("vendor", options={"mapping": {"vendorId": "id"}})
     * @ParamConverter("project", options={"mapping": {"projectId" : "id"}})
     *
     * @param Vendor $vendor
     * @param Project $project
     * @param string $currency
     *
     * @return JsonResponse
     */
    public function purchasesByPreliminaryInvoiceCandidate(
        Vendor $vendor,
        Project $project,
        string $currency
    ): JsonResponse {
        $purchases = $this->smartcardPurchaseService->getBy($vendor, $project, $currency);

        return $this->json($purchases);
    }
}
