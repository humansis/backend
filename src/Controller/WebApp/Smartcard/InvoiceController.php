<?php

declare(strict_types=1);

namespace Controller\WebApp\Smartcard;

use InputType\SmartcardInvoice;
use Pagination\Paginator;
use FOS\RestBundle\Controller\Annotations as Rest;
use Controller\WebApp\AbstractWebAppController;
use Enum\VendorInvoicingState;
use InputType\SmartcardRedemptionBatchCreateInputType;
use Repository\Smartcard\PreliminaryInvoiceRepository;
use Request\Pagination;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Controller\VendorApp\SmartcardController;
use Entity\Invoice;
use Entity\Vendor;
use Utils\SmartcardService;

class InvoiceController extends AbstractWebAppController
{
    /**
     * @Rest\Get("/web-app/v1/smartcard-redemption-batches/{id}/exports")
     *
     * @param Invoice $invoice
     *
     * @return JsonResponse
     */
    public function export(Invoice $invoice): Response
    {
        return $this->forward(SmartcardController::class . '::export', ['invoice' => $invoice]);
    }

    /**
     * @Rest\Get("/web-app/v1/smartcard-redemption-batches/{id}/legacy-exports")
     *
     * @param Invoice $invoice
     *
     * @return JsonResponse
     */
    public function legacyExport(Invoice $invoice): Response
    {
        return $this->forward(SmartcardController::class . '::exportLegacy', ['invoice' => $invoice]);
    }

    /**
     * @Rest\Get("/web-app/v1/vendors/{id}/smartcard-redemption-batches")
     *
     * @param Vendor $vendor
     * @param Pagination $pagination
     *
     * @return JsonResponse
     */
    public function invoices(Vendor $vendor, Pagination $pagination): JsonResponse
    {
        $invoices = $this->getDoctrine()->getRepository(Invoice::class)
            ->findByVendor($vendor, $pagination);

        return $this->json($invoices);
    }

    /**
     * @Rest\Get("/vendor-app/v2/vendors/{id}/smartcard-redemption-batches")
     *
     * @param Vendor $vendor
     * @param Pagination $pagination
     *
     * @return JsonResponse
     */
    public function invoicesForVendorApp(Vendor $vendor, Pagination $pagination): Response
    {
        return $this->forward(self::class . '::invoices', ['vendor' => $vendor, 'pagination' => $pagination]);
    }

    /**
     * @Rest\Post("/web-app/v1/vendors/{id}/smartcard-redemption-batches")
     *
     * @param Vendor $vendor
     * @param SmartcardRedemptionBatchCreateInputType $inputType
     * @param SmartcardService $smartcardService
     *
     * @return JsonResponse
     */
    public function create(
        Vendor $vendor,
        SmartcardRedemptionBatchCreateInputType $inputType,
        SmartcardService $smartcardService
    ): JsonResponse {
        //backward compatibility
        $newInvoice = new SmartcardInvoice();
        $newInvoice->setPurchases($inputType->getPurchaseIds());

        $invoice = $smartcardService->redeem($vendor, $newInvoice, $this->getUser());

        return $this->json($invoice);
    }

    /**
     * @Rest\Get("/web-app/v1/vendors/{id}/smartcard-redemption-candidates")
     *
     * @param Vendor $vendor
     * @param PreliminaryInvoiceRepository $invoiceRepository
     *
     * @return JsonResponse
     */
    public function preliminaryInvoices(Vendor $vendor, PreliminaryInvoiceRepository $invoiceRepository): JsonResponse
    {
        $invoices = $invoiceRepository->findByVendorAndState($vendor);

        return $this->json(new Paginator($invoices));
    }

    /**
     * @Rest\Get("/vendor-app/v2/vendors/{id}/smartcard-redemption-candidates")
     * @param Vendor $vendor
     *
     * @return JsonResponse
     * @deprecated use $this->candidatesForVendorApp()
     *
     */
    public function preliminariesForVendorAppDeprecated(Vendor $vendor): Response
    {
        return $this->forward(self::class . '::preliminaryInvoices', ['vendor' => $vendor]);
    }

    /**
     * @Rest\Get("/vendor-app/v3/vendors/{id}/smartcard-redemption-candidates")
     *
     * @param Vendor $vendor
     * @param PreliminaryInvoiceRepository $invoiceRepository
     *
     * @return JsonResponse
     */
    public function preliminariesForVendorApp(Vendor $vendor, PreliminaryInvoiceRepository $invoiceRepository): Response
    {
        $invoices = $invoiceRepository->findByVendorAndState($vendor, VendorInvoicingState::TO_REDEEM);

        return $this->json($invoices, 200, [], ['version' => 3]);
    }
}
