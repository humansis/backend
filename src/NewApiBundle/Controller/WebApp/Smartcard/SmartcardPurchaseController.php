<?php declare(strict_types=1);

namespace NewApiBundle\Controller\WebApp\Smartcard;

use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\Component\Smartcard\SmartcardPurchaseService;
use NewApiBundle\Controller\WebApp\AbstractWebAppController;
use NewApiBundle\InputType\SmartcardPurchaseFilterInputType;
use NewApiBundle\Request\Pagination;
use ProjectBundle\Entity\Project;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\JsonResponse;
use VoucherBundle\Entity\SmartcardPurchase;
use VoucherBundle\Entity\Invoice;
use VoucherBundle\Entity\Vendor;

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
     * @ParamConverter("redemptionBatch", class="Invoice")
     *
     * @param Invoice    $redemptionBatch
     * @param Pagination $pagination
     *
     * @return JsonResponse
     */
    public function purchasesByRedemptionBatch(Invoice $redemptionBatch, Pagination $pagination): JsonResponse
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
    public function purchasesByPreliminaryInvoiceCandidate(Vendor $vendor, Project $project, string $currency): JsonResponse
    {
        $purchases = $this->smartcardPurchaseService->getBy($vendor, $project, $currency);

        return $this->json($purchases);
    }
}
