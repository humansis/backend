<?php

declare(strict_types=1);

namespace Controller;

use Doctrine\Persistence\ManagerRegistry;
use Entity\Beneficiary;
use Entity\Household;
use Export\DistributedSummarySpreadsheetExport;
use FOS\RestBundle\Controller\Annotations as Rest;
use Entity\DistributedItem;
use InputType\DistributedItemFilterInputType;
use InputType\DistributedItemOrderInputType;
use Repository\DistributedItemRepository;
use Request\Pagination;
use Entity\Project;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class DistributedItemController extends AbstractController
{
    public function __construct(private readonly DistributedSummarySpreadsheetExport $distributedSummarySpreadsheetExport, private readonly ManagerRegistry $managerRegistry)
    {
    }

    /**
     * @Rest\Get("/web-app/v1/beneficiaries/{id}/distributed-items")
     * @ParamConverter("beneficiary")
     *
     *
     */
    public function listByBeneficiary(Beneficiary $beneficiary): JsonResponse
    {
        $data = $this->managerRegistry->getRepository(DistributedItem::class)
            ->findByBeneficiary($beneficiary);

        return $this->json($data);
    }

    /**
     * @Rest\Get("/web-app/v1/households/{id}/distributed-items")
     * @ParamConverter("household")
     *
     *
     */
    public function listByHousehold(Household $household): JsonResponse
    {
        $data = $this->managerRegistry->getRepository(DistributedItem::class)
            ->findByHousehold($household);

        return $this->json($data);
    }

    /**
     * @Rest\Get("/web-app/v1/distributed-items")
     *
     *
     */
    public function distributedItems(
        Request $request,
        DistributedItemFilterInputType $inputType,
        DistributedItemOrderInputType $order,
        DistributedItemRepository $distributedItemRepository,
        Pagination $pagination
    ): JsonResponse {
        if (!$request->headers->has('country')) {
            throw $this->createNotFoundException('Missing header attribute country');
        }

        $data = $distributedItemRepository->findByParams(
            $request->headers->get('country'),
            $inputType,
            $order,
            $pagination
        );

        return $this->json($data);
    }

    /**
     * @Rest\Get("/web-app/v1/distributed-items/exports")
     *
     *
     */
    public function summaryExports(Request $request, DistributedItemFilterInputType $inputType): Response
    {
        if (!$request->headers->has('country')) {
            throw $this->createNotFoundException('Missing header attribute country');
        }

        $filename = $this->distributedSummarySpreadsheetExport->export(
            $request->headers->get('country'),
            $request->get('type'),
            $inputType
        );

        $response = new BinaryFileResponse($filename);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, basename((string) $filename));

        return $response;
    }
}
