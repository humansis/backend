<?php

declare(strict_types=1);

namespace Controller\WebApp\Smartcard;

use Component\Smartcard\Invoice\InvoiceFactory;
use Component\Smartcard\Invoice\PreliminaryInvoiceService;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Component\Country\Countries;
use Export\SmartcardInvoiceExport;
use Export\SmartcardInvoiceLegacyExport;
use Pagination\Paginator;
use FOS\RestBundle\Controller\Annotations as Rest;
use Controller\WebApp\AbstractWebAppController;
use InputType\SmartcardInvoiceCreateInputType;
use Repository\SmartcardInvoiceRepository;
use Repository\OrganizationRepository;
use Request\Pagination;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Entity\Invoice;
use Entity\Vendor;
use Utils\Response\BinaryFileResponse;

class InvoiceController extends AbstractWebAppController
{
    use BinaryFileResponse;

    /**
     * @Rest\Get("/web-app/v1/smartcard-redemption-batches/{id}/exports")
     *
     * @param Invoice $invoice
     * @param Countries $countries
     * @param SmartcardInvoiceExport $smartcardInvoiceExport
     * @param OrganizationRepository $organizationRepository
     * @return JsonResponse
     */
    public function export(
        Invoice $invoice,
        Countries $countries,
        SmartcardInvoiceExport $smartcardInvoiceExport,
        OrganizationRepository $organizationRepository
    ): Response {
        $country = $countries->getCountry($invoice->getProject()->getCountryIso3());

        // todo find organisation by relation to smartcard
        $organization = $organizationRepository->findOneBy([]);
        $filename = $smartcardInvoiceExport->export(
            $invoice,
            $organization,
            $this->getUser(),
            $country->getLanguage()
        );

        return $this->createBinaryFileResponse($filename);
    }

    /**
     * @Rest\Get("/web-app/v1/smartcard-redemption-batches/{id}/legacy-exports")
     *
     * @param Invoice $invoice
     * @param OrganizationRepository $organizationRepository
     * @param SmartcardInvoiceLegacyExport $smartcardInvoiceLegacyExport
     * @return JsonResponse
     */
    public function legacyExport(
        Invoice $invoice,
        OrganizationRepository $organizationRepository,
        SmartcardInvoiceLegacyExport $smartcardInvoiceLegacyExport
    ): Response {
        // todo find organisation by relation to smartcard
        $organization = $organizationRepository->findOneBy([]);
        $filename = $smartcardInvoiceLegacyExport->export($invoice, $organization, $this->getUser());

        return $this->createBinaryFileResponse($filename);
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
        $invoice = $invoiceFactory->create($vendor, $inputType, $this->getUser());

        return $this->json($invoice);
    }

    /**
     * @Rest\Get("/web-app/v1/vendors/{id}/smartcard-redemption-candidates")
     *
     * @param Vendor $vendor
     * @param PreliminaryInvoiceService $preliminaryInvoiceService
     * @return JsonResponse
     */
    public function preliminaryInvoices(
        Vendor $vendor,
        PreliminaryInvoiceService $preliminaryInvoiceService
    ): JsonResponse {
        return $this->json(new Paginator($preliminaryInvoiceService->getArrayOfPreliminaryInvoicesDtoByVendor($vendor)));
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
