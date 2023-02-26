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
    public function __construct(private readonly SmartcardPurchaseService $smartcardPurchaseService)
    {
    }

    #[Rest\Get('/web-app/v1/smartcard-purchases')]
    #[Rest\Get('/vendor-app/v2/smartcard-purchases')]
    public function purchases(
        SmartcardPurchaseFilterInputType $filter,
        Pagination $pagination,
        SmartcardPurchaseRepository $smartcardPurchaseRepository
    ): JsonResponse {
        $purchases = $smartcardPurchaseRepository->findByParams($filter, $pagination);

        return $this->json($purchases);
    }

    #[Rest\Get('/web-app/v1/smartcard-redemption-batches/{id}/smartcard-purchases')]
    #[ParamConverter('invoice', class: 'Entity\Invoice')]
    public function purchasesByInvoice(
        Invoice $invoice,
        Pagination $pagination,
        SmartcardPurchaseRepository $smartcardPurchaseRepository
    ): JsonResponse {
        $purchases = $smartcardPurchaseRepository->findByBatch($invoice, $pagination);

        return $this->json($purchases);
    }

    #[Rest\Get('/vendor-app/v1/vendors/{vendorId}/projects/{projectId}/currencies/{currency}/smartcard-purchases')]
    #[ParamConverter('vendor', options: ['mapping' => ['vendorId' => 'id']])]
    #[ParamConverter('project', options: ['mapping' => ['projectId' => 'id']])]
    public function purchasesByPreliminaryInvoiceCandidate(
        Vendor $vendor,
        Project $project,
        string $currency
    ): JsonResponse {
        $purchases = $this->smartcardPurchaseService->getBy($vendor, $project, $currency);

        return $this->json($purchases);
    }
}
