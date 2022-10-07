<?php

declare(strict_types=1);

namespace Controller;

use Entity\Beneficiary;
use Entity\Household;
use FOS\RestBundle\Controller\Annotations as Rest;
use Entity\SmartcardPurchasedItem;
use Export\SmartcardPurchasedItemSpreadsheet;
use InputType\PurchasedItemFilterInputType;
use InputType\PurchasedItemOrderInputType;
use InputType\SmartcardPurchasedItemFilterInputType;
use Repository\SmartcardPurchasedItemRepository;
use Request\Pagination;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Entity\PurchasedItem;
use Repository\PurchasedItemRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class PurchasedItemController extends AbstractController
{
    /** @var SmartcardPurchasedItemSpreadsheet */
    private $smartcardPurchasedItemSpreadsheet;

    public function __construct(SmartcardPurchasedItemSpreadsheet $smartcardPurchasedItemSpreadsheet)
    {
        $this->smartcardPurchasedItemSpreadsheet = $smartcardPurchasedItemSpreadsheet;
    }

    /**
     * @Rest\Get("/web-app/v1/beneficiaries/{id}/purchased-items")
     * @ParamConverter("beneficiary")
     *
     * @param Beneficiary $beneficiary
     *
     * @return JsonResponse
     */
    public function listByBeneficiary(Beneficiary $beneficiary): JsonResponse
    {
        /** @var PurchasedItemRepository $repository */
        $repository = $this->getDoctrine()->getRepository(PurchasedItem::class);

        $data = $repository->findByBeneficiary($beneficiary);

        return $this->json($data);
    }

    /**
     * @Rest\Get("/web-app/v1/households/{id}/purchased-items")
     * @ParamConverter("household")
     *
     * @param Household $household
     *
     * @return JsonResponse
     */
    public function listByHousehold(Household $household): JsonResponse
    {
        /** @var PurchasedItemRepository $repository */
        $repository = $this->getDoctrine()->getRepository(PurchasedItem::class);

        $data = $repository->findByHousehold($household);

        return $this->json($data);
    }

    /**
     * @Rest\Get("/web-app/v1/purchased-items")
     *
     * @return JsonResponse
     *
     * @deprecated This endpoint is deprecated and will be removed soon
     */
    public function list(
        Request $request,
        PurchasedItemFilterInputType $filterInputType,
        PurchasedItemOrderInputType $order,
        Pagination $pagination
    ): JsonResponse {
        if (!$request->headers->has('country')) {
            throw $this->createNotFoundException('Missing header attribute country');
        }

        /** @var PurchasedItemRepository $repository */
        $repository = $this->getDoctrine()->getRepository(PurchasedItem::class);

        $data = $repository->findByParams($request->headers->get('country'), $filterInputType, $order, $pagination);

        return $this->json($data);
    }

    /**
     * @Rest\Get("/web-app/v1/purchased-items/exports")
     *
     * @param Request $request
     * @param PurchasedItemFilterInputType $filter
     *
     * @return Response
     *
     * @deprecated This endpoint is deprecated and will be removed soon
     */
    public function summaryExports(Request $request, PurchasedItemFilterInputType $filter): Response
    {
        if (!$request->headers->has('country')) {
            throw $this->createNotFoundException('Missing header attribute country');
        }

        $filename = $this->get('export.purchased_summary.spreadsheet')->export(
            $request->headers->get('country'),
            $request->get('type'),
            $filter
        );

        $response = new BinaryFileResponse($filename);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, basename($filename));

        return $response;
    }

    /**
     * @Rest\Get("/web-app/v1/smartcard-purchased-items")
     *
     * @param Request $request
     * @param SmartcardPurchasedItemFilterInputType $filterInputType
     * @param PurchasedItemOrderInputType $order
     * @param SmartcardPurchasedItemRepository $purchasedItemRepository
     * @param Pagination $pagination
     *
     * @return JsonResponse
     */
    public function listSmartcardItems(
        Request $request,
        SmartcardPurchasedItemFilterInputType $filterInputType,
        PurchasedItemOrderInputType $order,
        SmartcardPurchasedItemRepository $purchasedItemRepository,
        Pagination $pagination
    ): JsonResponse {
        if (!$request->headers->has('country')) {
            throw $this->createNotFoundException('Missing header attribute country');
        }

        $data = $purchasedItemRepository->findByParams(
            $request->headers->get('country'),
            $filterInputType,
            $order,
            $pagination
        );

        return $this->json($data);
    }

    /**
     * @Rest\Get("/web-app/v1/smartcard-purchased-items/exports")
     *
     * @param Request $request
     * @param SmartcardPurchasedItemFilterInputType $filter
     *
     * @return Response
     */
    public function exportSmartcardItems(Request $request, SmartcardPurchasedItemFilterInputType $filter): Response
    {
        if (!$request->headers->has('country')) {
            throw $this->createNotFoundException('Missing header attribute country');
        }

        $filename = $this->smartcardPurchasedItemSpreadsheet->export(
            $request->headers->get('country'),
            $request->get('type'),
            $filter
        );

        $response = new BinaryFileResponse($filename);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, basename($filename));

        return $response;
    }
}
