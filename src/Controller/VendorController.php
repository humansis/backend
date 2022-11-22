<?php

namespace Controller;

use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Enum\EnumValueNoFoundException;
use Exception;
use FOS\RestBundle\Controller\Annotations as Rest;
use InputType\VendorCreateInputType;
use InputType\VendorFilterInputType;
use InputType\VendorOrderInputType;
use InputType\VendorUpdateInputType;
use Repository\SmartcardPurchaseRepository;
use Repository\VendorRepository;
use Request\Pagination;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Entity\Vendor;
use Utils\VendorService;

class VendorController extends AbstractController
{
    /**
     * @var VendorService
     */
    private $vendorService;

    public function __construct(VendorService $vendorService)
    {
        $this->vendorService = $vendorService;
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
     * @param VendorRepository $vendorRepository
     * @return JsonResponse
     * @throws EnumValueNoFoundException
     */
    public function list(
        Request $request,
        VendorFilterInputType $filter,
        Pagination $pagination,
        VendorOrderInputType $orderBy,
        VendorRepository $vendorRepository
    ): JsonResponse {
        if (!$request->headers->has('country')) {
            throw $this->createNotFoundException('Missing header attribute country');
        }

        return $this->json(
            $vendorRepository->findByParams($request->headers->get('country'), $filter, $orderBy, $pagination)
        );
    }

    /**
     * @Rest\Post("/web-app/v1/vendors")
     *
     * @param VendorCreateInputType $inputType
     * @return JsonResponse
     *
     * @throws EntityNotFoundException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function create(VendorCreateInputType $inputType): JsonResponse
    {
        $object = $this->vendorService->create($inputType);

        return $this->json($object);
    }

    /**
     * @Rest\Put("/web-app/v1/vendors/{id}")
     *
     * @param Vendor $vendor
     * @param VendorUpdateInputType $inputType
     * @return JsonResponse
     * @throws EntityNotFoundException
     * @throws ORMException
     * @throws OptimisticLockException
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
     * @param Vendor $vendor
     *
     * @return JsonResponse
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
     * @param Vendor $vendor
     *
     * @return Response
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
     * @param Vendor $vendor
     * @param SmartcardPurchaseRepository $smartcardPurchaseRepository
     * @return Response
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
