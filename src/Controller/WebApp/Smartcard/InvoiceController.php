<?php

declare(strict_types=1);

namespace Controller\WebApp\Smartcard;

use Component\Smartcard\Invoice\Exception\NotRedeemableInvoiceException;
use Component\Smartcard\Invoice\InvoiceFactory;
use Component\Smartcard\Invoice\PreliminaryInvoiceDto;
use Component\Smartcard\Invoice\PreliminaryInvoiceService;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Pagination\Paginator;
use FOS\RestBundle\Controller\Annotations as Rest;
use Controller\WebApp\AbstractWebAppController;
use InputType\SmartcardInvoiceCreateInputType;
use Repository\Smartcard\PreliminaryInvoiceRepository;
use Repository\SmartcardInvoiceRepository;
use Request\Pagination;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Controller\VendorApp\SmartcardController;
use Entity\Invoice;
use Entity\Vendor;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

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
     * @param SmartcardInvoiceRepository $smartcardInvoiceRepository
     * @return JsonResponse
     */
    public function invoices(Vendor $vendor, Pagination $pagination, SmartcardInvoiceRepository $smartcardInvoiceRepository): JsonResponse
    {
        $invoices = $smartcardInvoiceRepository->findByVendor($vendor, $pagination);

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
     * @param SmartcardInvoiceCreateInputType $inputType
     * @param InvoiceFactory $invoiceFactory
     * @return JsonResponse
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function create(
        Vendor $vendor,
        SmartcardInvoiceCreateInputType $inputType,
        InvoiceFactory $invoiceFactory
    ): JsonResponse {
        try {
            $invoice = $invoiceFactory->create($vendor, $inputType, $this->getUser());
        } catch (NotRedeemableInvoiceException $e) {
            throw new BadRequestHttpException($e->getMessage(), $e);
        }

        return $this->json($invoice);
    }

    /**
     * @Rest\Get("/web-app/v1/vendors/{id}/smartcard-redemption-candidates")
     *
     * @param Vendor $vendor
     * @param PreliminaryInvoiceRepository $preliminaryInvoiceRepository
     * @param InvoiceFactory $invoiceFactory
     * @return JsonResponse
     */
    public function preliminaryInvoices(
        Vendor $vendor,
        PreliminaryInvoiceRepository $preliminaryInvoiceRepository,
        InvoiceFactory $invoiceFactory
    ): JsonResponse {
        $preliminaryInvoices = $preliminaryInvoiceRepository->findBy(['vendor' => $vendor]);

        $preliminaryInvoicesDto = [];
        foreach ($preliminaryInvoices as $preliminaryInvoice) {
            try {
                $invoiceFactory->checkIfPurchasesCanBeInvoiced(
                    $vendor,
                    $preliminaryInvoice->getPurchaseIds()
                );
                $canRedeem = true;
            } catch (NotRedeemableInvoiceException $e) {
                $canRedeem = false;
            }
            $preliminaryInvoicesDto[] = new PreliminaryInvoiceDto($preliminaryInvoice, $canRedeem);
        }

        return $this->json(new Paginator($preliminaryInvoicesDto));
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
     * @param PreliminaryInvoiceService $preliminaryInvoiceService
     * @return JsonResponse
     */
    public function preliminariesForVendorApp(Vendor $vendor, PreliminaryInvoiceService $preliminaryInvoiceService): Response
    {
        $preliminaryInvoices = $preliminaryInvoiceService->getRedeemablePreliminaryInvoicesByVendor($vendor);

        return $this->json($preliminaryInvoices, 200, [], ['version' => 3]);
    }
}
