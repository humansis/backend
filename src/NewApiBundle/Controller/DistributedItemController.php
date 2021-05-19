<?php

declare(strict_types=1);

namespace NewApiBundle\Controller;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Household;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\Entity\DistributedItem;
use NewApiBundle\InputType\DistributedItemFilterInputType;
use NewApiBundle\InputType\DistributedItemOrderInputType;
use NewApiBundle\Request\Pagination;
use ProjectBundle\Entity\Project;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class DistributedItemController extends AbstractController
{
    /**
     * @Rest\Get("/beneficiaries/{id}/distributed-items")
     * @ParamConverter("beneficiary")
     *
     * @param Beneficiary $beneficiary
     *
     * @return JsonResponse
     */
    public function listByBeneficiary(Beneficiary $beneficiary): JsonResponse
    {
        $data = $this->getDoctrine()->getRepository(DistributedItem::class)
            ->findByBeneficiary($beneficiary);

        return $this->json($data);
    }

    /**
     * @Rest\Get("/households/{id}/distributed-items")
     * @ParamConverter("household")
     *
     * @param Household $household
     *
     * @return JsonResponse
     */
    public function listByHousehold(Household $household): JsonResponse
    {
        $data = $this->getDoctrine()->getRepository(DistributedItem::class)
            ->findByHousehold($household);

        return $this->json($data);
    }

    /**
     * @Rest\Get("/distributed-items")
     *
     * @param Request                        $request
     * @param DistributedItemFilterInputType $inputType
     * @param DistributedItemOrderInputType  $order
     * @param Pagination                     $pagination
     *
     * @return JsonResponse
     */
    public function distributedItems(
        Request $request,
        DistributedItemFilterInputType $inputType,
        DistributedItemOrderInputType $order,
        Pagination $pagination
    ): JsonResponse
    {
        if (!$request->headers->has('country')) {
            throw $this->createNotFoundException('Missing header attribute country');
        }

        $data = $this->getDoctrine()->getRepository(DistributedItem::class)
            ->findByParams($request->headers->get('country'), $inputType, $order, $pagination);

        return $this->json($data);
    }

    /**
     * @Rest\Get("/distributed-items/exports")
     *
     * @param Request                        $request
     * @param DistributedItemFilterInputType $inputType
     *
     * @return Response
     */
    public function summaryExports(Request $request, DistributedItemFilterInputType $inputType): Response
    {
        if (!$request->headers->has('country')) {
            throw $this->createNotFoundException('Missing header attribute country');
        }

        $filename = $this->get('export.distributed_summary.spreadsheet')->export($request->headers->get('country'), $request->get('type'), $inputType);

        $response = new BinaryFileResponse($filename);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, basename($filename));

        return $response;
    }
}
