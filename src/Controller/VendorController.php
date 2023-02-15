<?php

namespace Controller;

use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
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
use Utils\ExportTableServiceInterface;
use Utils\VendorService;
use Utils\VendorTransformData;

class VendorController extends AbstractController
{
    public function __construct(private readonly VendorService $vendorService, private readonly VendorRepository $vendorRepository, private readonly VendorTransformData $vendorTransformData, private readonly ExportTableServiceInterface $exportTableService)
    {
    }

    /**
     *
     * @return JsonResponse
     */
    #[Rest\Get('/web-app/v1/vendors/exports')]
    public function exports(Request $request): Response
    {
        $vendors = $this->vendorRepository->findByCountry($request->headers->get('country'));
        $exportableTable = $this->vendorTransformData->transformData($vendors);

        return $this->exportTableService->export($exportableTable, 'vendors', $request->query->get('type'));
    }

    #[Rest\Get('/web-app/v1/vendors/{id}')]
    public function item(Vendor $vendor): JsonResponse
    {
        if (true === $vendor->getArchived()) {
            throw $this->createNotFoundException();
        }

        return $this->json($vendor);
    }

    /**
     * @throws EnumValueNoFoundException
     */
    #[Rest\Get('/web-app/v1/vendors')]
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
     * @throws EntityNotFoundException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    #[Rest\Post('/web-app/v1/vendors')]
    public function create(VendorCreateInputType $inputType): JsonResponse
    {
        $object = $this->vendorService->create($inputType);

        return $this->json($object);
    }

    /**
     *
     * @throws EntityNotFoundException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    #[Rest\Put('/web-app/v1/vendors/{id}')]
    public function update(Vendor $vendor, VendorUpdateInputType $inputType): JsonResponse
    {
        if ($vendor->getArchived()) {
            throw new BadRequestHttpException('Unable to update archived vendor.');
        }

        $object = $this->vendorService->update($vendor, $inputType);

        return $this->json($object);
    }

    /**
     * @throws Exception
     */
    #[Rest\Delete('/web-app/v1/vendors/{id}')]
    public function delete(Vendor $vendor): JsonResponse
    {
        $this->vendorService->archiveVendor($vendor, true);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @throws Exception
     */
    #[Rest\Get('/web-app/v1/vendors/{id}/invoice')]
    public function invoice(Vendor $vendor): Response
    {
        return $this->vendorService->printInvoice($vendor);
    }

    /**
     *
     * @throws NonUniqueResultException
     */
    #[Rest\Get('/web-app/v1/vendors/{id}/summaries')]
    public function summaries(Vendor $vendor, SmartcardPurchaseRepository $smartcardPurchaseRepository): Response
    {
        $summary = $smartcardPurchaseRepository->countPurchases($vendor);

        return $this->json([
            'redeemedSmartcardPurchasesTotalCount' => $summary->getCount(),
            'redeemedSmartcardPurchasesTotalValue' => $summary->getValue(),
        ]);
    }
}
