<?php declare(strict_types=1);

namespace NewApiBundle\Controller\WebApp\Smartcard;

use CommonBundle\Pagination\Paginator;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\Controller\WebApp\AbstractWebAppController;
use NewApiBundle\Entity\Smartcard\PreliminaryInvoice;
use NewApiBundle\InputType\SmartcardRedemptionBatchCreateInputType;
use NewApiBundle\Repository\Smartcard\PreliminaryInvoiceRepository;
use NewApiBundle\Request\Pagination;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use VoucherBundle\Controller\SmartcardController;
use VoucherBundle\Entity\SmartcardPurchase;
use VoucherBundle\Entity\Invoice;
use VoucherBundle\Entity\Vendor;
use VoucherBundle\Utils\SmartcardService;

class InvoiceController extends AbstractWebAppController
{
    /**
     * @Rest\Get("/web-app/v1/smartcard-redemption-batches/{id}/exports")
     * @ParamConverter("redemptionBatch", class="Invoice")
     *
     * @param Invoice $redemptionBatch
     *
     * @return JsonResponse
     */
    public function export(Invoice $redemptionBatch): Response
    {
        return $this->forward(SmartcardController::class.'::export', ['batch' => $redemptionBatch]);
    }

    /**
     * @Rest\Get("/web-app/v1/smartcard-redemption-batches/{id}/legacy-exports")
     * @ParamConverter("invoice", class="Invoice")
     *
     * @param Invoice $invoice
     *
     * @return JsonResponse
     */
    public function legacyExport(Invoice $invoice): Response
    {
        return $this->forward(SmartcardController::class.'::exportLegacy', ['batch' => $invoice]);
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
        return $this->forward(self::class.'::invoices', ['vendor' => $vendor, 'pagination' => $pagination]);
    }

    /**
     * @Rest\Post("/web-app/v1/vendors/{id}/smartcard-redemption-batches")
     *
     * @param Vendor                                  $vendor
     * @param SmartcardRedemptionBatchCreateInputType $inputType
     * @param SmartcardService                        $smartcardService
     *
     * @return JsonResponse
     */
    public function create(Vendor $vendor, SmartcardRedemptionBatchCreateInputType $inputType, SmartcardService $smartcardService): JsonResponse
    {
        //backward compatibility
        $newInvoice = new \VoucherBundle\InputType\SmartcardInvoice();
        $newInvoice->setPurchases($inputType->getPurchaseIds());

        $redemptionBath = $smartcardService->redeem($vendor, $newInvoice, $this->getUser());

        return $this->json($redemptionBath);
    }

    /**
     * @Rest\Get("/web-app/v1/vendors/{id}/smartcard-redemption-candidates")
     *
     * @param Vendor                       $vendor
     * @param PreliminaryInvoiceRepository $invoiceRepository
     *
     * @return JsonResponse
     */
    public function preliminaryInvoices(Vendor $vendor, PreliminaryInvoiceRepository $invoiceRepository): JsonResponse
    {
        $invoices = $invoiceRepository->findBy(['vendor' => $vendor]);

        return $this->json(new Paginator($invoices));
    }

    /**
     * @Rest\Get("/vendor-app/v2/vendors/{id}/smartcard-redemption-candidates")
     * @deprecated use $this->candidatesForVendorApp()
     *
     * @param Vendor $vendor
     *
     * @return JsonResponse
     */
    public function preliminariesForVendorAppDeprecated(Vendor $vendor): Response
    {
        return $this->forward(self::class.'::preliminaryInvoices', ['vendor' => $vendor]);
    }

    /**
     * @Rest\Get("/vendor-app/v3/vendors/{id}/smartcard-redemption-candidates")
     *
     * @param Vendor $vendor
     *
     * @return JsonResponse
     */
    public function preliminariesForVendorApp(Vendor $vendor, PreliminaryInvoiceRepository $invoiceRepository): Response
    {
        $invoices = $invoiceRepository->findBy(['vendor' => $vendor]);

        return $this->json($invoices, 200, [], ['version' => 3]);
    }
}
