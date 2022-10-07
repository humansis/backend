<?php

namespace Controller;

use Controller\ExportController;
use Enum\EnumValueNoFoundException;
use Exception;
use FOS\RestBundle\Controller\Annotations as Rest;
use InputType\VendorCreateInputType;
use InputType\VendorFilterInputType;
use InputType\VendorOrderInputType;
use InputType\VendorUpdateInputType;
use Request\Pagination;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Entity\SmartcardPurchase;
use Entity\Vendor;
use Repository\VendorRepository;

class VendorController extends AbstractController
{
    /**
     * @var VendorRepository
     */
    private $vendorRepository;

    public function __construct(VendorRepository $vendorRepository)
    {
        $this->vendorRepository = $vendorRepository;
    }

    /**
     * @Rest\Get("/web-app/v1/vendors/exports")
     *
     * @param Request $request
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
     * @param Vendor $vendor
     *
     * @return JsonResponse
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
     * @param Request $request
     * @param VendorFilterInputType $filter
     * @param Pagination $pagination
     * @param VendorOrderInputType $orderBy
     *
     * @return JsonResponse
     * @throws EnumValueNoFoundException
     */
    public function list(
        Request $request,
        VendorFilterInputType $filter,
        Pagination $pagination,
        VendorOrderInputType $orderBy
    ): JsonResponse {
        if (!$request->headers->has('country')) {
            throw $this->createNotFoundException('Missing header attribute country');
        }

        $data = $this->vendorRepository->findByParams(
            $request->headers->get('country'),
            $filter,
            $orderBy,
            $pagination
        );

        return $this->json($data);
    }

    /**
     * @Rest\Post("/web-app/v1/vendors")
     *
     * @param VendorCreateInputType $inputType
     *
     * @return JsonResponse
     *
     * @throws Exception
     */
    public function create(VendorCreateInputType $inputType): JsonResponse
    {
        $object = $this->get('voucher.vendor_service')->create($inputType);

        return $this->json($object);
    }

    /**
     * @Rest\Put("/web-app/v1/vendors/{id}")
     *
     * @param Vendor $vendor
     * @param VendorUpdateInputType $inputType
     *
     * @return JsonResponse
     */
    public function update(Vendor $vendor, VendorUpdateInputType $inputType): JsonResponse
    {
        if ($vendor->getArchived()) {
            throw new BadRequestHttpException('Unable to update archived vendor.');
        }

        $object = $this->get('voucher.vendor_service')->update($vendor, $inputType);

        return $this->json($object);
    }

    /**
     * @Rest\Delete("/web-app/v1/vendors/{id}")
     *
     * @param Vendor $vendor
     *
     * @return JsonResponse
     *
     * @throws Exception
     */
    public function delete(Vendor $vendor): JsonResponse
    {
        $this->get('voucher.vendor_service')->archiveVendor($vendor, true);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @Rest\Get("/web-app/v1/vendors/{id}/invoice")
     *
     * @param Vendor $vendor
     *
     * @return Response
     *
     * @throws Exception
     */
    public function invoice(Vendor $vendor): Response
    {
        return $this->get('voucher.vendor_service')->printInvoice($vendor);
    }

    /**
     * @Rest\Get("/web-app/v1/vendors/{id}/summaries")
     *
     * @param Vendor $vendor
     *
     * @return Response
     *
     * @throws Exception
     */
    public function summaries(Vendor $vendor): Response
    {
        $summary = $this->getDoctrine()->getRepository(SmartcardPurchase::class)
            ->countPurchases($vendor);

        return $this->json([
            'redeemedSmartcardPurchasesTotalCount' => $summary->getCount(),
            'redeemedSmartcardPurchasesTotalValue' => $summary->getValue(),
        ]);
    }
}
