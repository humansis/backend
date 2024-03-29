<?php

declare(strict_types=1);

namespace Controller\WebApp\Smartcard;

use Component\Smartcard\Invoice\InvoiceFactory;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Component\Country\Countries;
use Export\SmartcardInvoiceExport;
use Export\SmartcardInvoiceLegacyExport;
use Pagination\Paginator;
use FOS\RestBundle\Controller\Annotations as Rest;
use Controller\WebApp\AbstractWebAppController;
use InputType\SmartcardInvoiceCreateInputType;
use Psr\Log\LoggerInterface;
use Repository\Smartcard\PreliminaryInvoiceRepository;
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

    #[Rest\Get('/web-app/v1/smartcard-redemption-batches/{id}/exports')]
    public function export(
        Invoice $invoice,
        Countries $countries,
        SmartcardInvoiceExport $smartcardInvoiceExport,
        OrganizationRepository $organizationRepository,
        LoggerInterface $logger
    ): Response {
        $country = $countries->getCountry($invoice->getProject()->getCountryIso3());

        $logger->info('[translations] Print invoice #' . $invoice->getId() . ' in language: ' . $country->getLanguage());

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

    #[Rest\Get('/web-app/v1/smartcard-redemption-batches/{id}/legacy-exports')]
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

    #[Rest\Get('/web-app/v1/vendors/{id}/smartcard-redemption-batches')]
    public function invoices(
        Vendor $vendor,
        Pagination $pagination,
        SmartcardInvoiceRepository $smartcardInvoiceRepository
    ): JsonResponse {
        $invoices = $smartcardInvoiceRepository->findByVendor($vendor, $pagination);

        return $this->json($invoices);
    }

    /**
     *
     * @return JsonResponse
     */
    #[Rest\Get('/vendor-app/v2/vendors/{id}/smartcard-redemption-batches')]
    public function invoicesForVendorApp(Vendor $vendor, Pagination $pagination): Response
    {
        return $this->forward(self::class . '::invoices', ['vendor' => $vendor, 'pagination' => $pagination]);
    }

    /**
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    #[Rest\Post('/web-app/v1/vendors/{id}/smartcard-redemption-batches')]
    public function create(
        Vendor $vendor,
        SmartcardInvoiceCreateInputType $inputType,
        InvoiceFactory $invoiceFactory
    ): JsonResponse {
        $invoice = $invoiceFactory->create($vendor, $inputType, $this->getUser());

        return $this->json($invoice);
    }

    #[Rest\Get('/web-app/v1/vendors/{id}/smartcard-redemption-candidates')]
    public function preliminaryInvoices(
        Vendor $vendor,
        PreliminaryInvoiceRepository $preliminaryInvoiceRepository
    ): JsonResponse {
        return $this->json(new Paginator($preliminaryInvoiceRepository->findBy(['vendor' => $vendor])));
    }

    /**
     * @return JsonResponse
     */
    #[Rest\Get('/vendor-app/v3/vendors/{id}/smartcard-redemption-candidates')]
    public function preliminariesForVendorApp(
        Vendor $vendor,
        PreliminaryInvoiceRepository $preliminaryInvoiceRepository
    ): Response {
        $preliminaryInvoices = $preliminaryInvoiceRepository->findBy(['vendor' => $vendor, 'isRedeemable' => true]);

        return $this->json($preliminaryInvoices, 200, [], ['version' => 3]);
    }
}
