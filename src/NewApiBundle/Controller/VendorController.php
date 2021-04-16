<?php

namespace NewApiBundle\Controller;

use CommonBundle\Controller\ExportController;
use Exception;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\Exception\ConstraintViolationException;
use NewApiBundle\Exception\NotUniqueException;
use NewApiBundle\InputType\VendorCreateInputType;
use NewApiBundle\InputType\VendorFilterInputType;
use NewApiBundle\InputType\VendorOrderInputType;
use NewApiBundle\InputType\VendorUpdateInputType;
use NewApiBundle\Request\Pagination;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\ConstraintViolation;
use VoucherBundle\Entity\Vendor;
use VoucherBundle\Repository\VendorRepository;

class VendorController extends AbstractController
{
    /**
     * @Rest\Get("/vendors/exports")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function exports(Request $request): JsonResponse
    {
        $request->query->add(['vendors' => true]);
        $request->query->add(['__country' => $request->headers->get('country')]);

        return $this->forward(ExportController::class.'::exportAction', [], $request->query->all());
    }

    /**
     * @Rest\Get("/vendors/{id}")
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
     * @Rest\Get("/vendors")
     *
     * @param Request               $request
     * @param VendorFilterInputType $filter
     * @param Pagination            $pagination
     * @param VendorOrderInputType  $orderBy
     *
     * @return JsonResponse
     */
    public function list(Request $request, VendorFilterInputType $filter, Pagination $pagination, VendorOrderInputType $orderBy): JsonResponse
    {
        if (!$request->headers->has('country')) {
            throw $this->createNotFoundException('Missing header attribute country');
        }

        /** @var VendorRepository $repository */
        $repository = $this->getDoctrine()->getRepository(Vendor::class);
        $data = $repository->findByParams($request->headers->get('country'), $filter, $orderBy, $pagination);

        return $this->json($data);
    }

    /**
     * @Rest\Post("/vendors")
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
     * @Rest\Put("/vendors/{id}")
     *
     * @param Vendor                $vendor
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
     * @Rest\Delete("/vendors/{id}")
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
     * @Rest\Get("/vendors/{id}/invoice")
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
}
