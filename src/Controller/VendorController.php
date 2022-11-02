<?php

namespace Controller;

use Component\Smartcard\Invoice\PreliminaryInvoiceService;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\NonUniqueResultException;
use Enum\EnumValueNoFoundException;
use Exception;
use FOS\RestBundle\Controller\Annotations as Rest;
use InputType\VendorCreateInputType;
use InputType\VendorFilterInputType;
use InputType\VendorOrderInputType;
use InputType\VendorUpdateInputType;
use Pagination\Paginator;
use Repository\SmartcardPurchaseRepository;
use Request\Pagination;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Entity\Vendor;
use Repository\VendorRepository;
use Utils\VendorService;

class VendorController extends AbstractController
{
    public function __construct(private readonly VendorRepository $vendorRepository, private readonly VendorService $vendorService)
    {
    }

    /**
     * @Rest\Get("/web-app/v1/vendors/exports")
     *
     *
     * @return JsonResponse
     */
    public function exports(Request $request): Response
    {
        $request->query->add(['vendors' => true]);
        $request->request->add(['__country' => $request->headers->get('country')]);

        return $this->forward(ExportController::class . '::exportAction', [], $request->query->all());
    }

    /**
     * @Rest\Get("/web-app/v1/vendors/{id}")
     *
     *
     */
    public function item(Vendor $vendor): JsonResponse
    {
        if (true === $vendor->getArchived()) {
            throw $this->createNotFoundException();
        }

        return $this->json($vendor);
    }

    /**
     * @Rest\Get("/web-app/v1/vendors")
     *
     * @throws EnumValueNoFoundException
     */
    public function list(
        Request $request,
        VendorFilterInputType $filter,
        Pagination $pagination,
        VendorOrderInputType $orderBy,
        PreliminaryInvoiceService $preliminaryInvoiceService
    ): JsonResponse {
        if (!$request->headers->has('country')) {
            throw $this->createNotFoundException('Missing header attribute country');
        }

        $vendors = $this->vendorRepository->findByParams(
            $request->headers->get('country'),
            $filter,
            $orderBy,
            $pagination
        );
        if ($filter->hasInvoicing()) {
            $vendors = $preliminaryInvoiceService->filterVendorsByInvoicing($vendors, $filter->getInvoicing());
        }

        return $this->json(new Paginator($vendors));
    }

    /**
     * @Rest\Post("/web-app/v1/vendors")
     *
     *
     * @throws EntityNotFoundException
     */
    public function create(VendorCreateInputType $inputType): JsonResponse
    {
        $object = $this->vendorService->create($inputType);

        return $this->json($object);
    }

    /**
     * @Rest\Put("/web-app/v1/vendors/{id}")
     *
     * @throws EntityNotFoundException
     */
    public function update(Vendor $vendor, VendorUpdateInputType $inputType): JsonResponse
    {
        if ($vendor->getArchived()) {
            throw new BadRequestHttpException('Unable to update archived vendor.');
        }

        $object = $this->vendorService->update($vendor, $inputType);

        return $this->json($object);
    }

    /**
     * @Rest\Delete("/web-app/v1/vendors/{id}")
     *
     *
     *
     * @throws Exception
     */
    public function delete(Vendor $vendor): JsonResponse
    {
        $this->vendorService->archiveVendor($vendor, true);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @Rest\Get("/web-app/v1/vendors/{id}/invoice")
     *
     *
     *
     * @throws Exception
     */
    public function invoice(Vendor $vendor): Response
    {
        return $this->vendorService->printInvoice($vendor);
    }

    /**
     * @Rest\Get("/web-app/v1/vendors/{id}/summaries")
     *
     *
     * @throws NonUniqueResultException
     */
    public function summaries(Vendor $vendor, SmartcardPurchaseRepository $smartcardPurchaseRepository): Response
    {
        $summary = $smartcardPurchaseRepository->countPurchases($vendor);

        return $this->json([
            'redeemedSmartcardPurchasesTotalCount' => $summary->getCount(),
            'redeemedSmartcardPurchasesTotalValue' => $summary->getValue(),
        ]);
    }
}
