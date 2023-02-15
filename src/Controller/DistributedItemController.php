<?php

declare(strict_types=1);

namespace Controller;

use Component\Country\Countries;
use Doctrine\Persistence\ManagerRegistry;
use Entity\Beneficiary;
use Entity\Household;
use FOS\RestBundle\Controller\Annotations as Rest;
use Entity\DistributedItem;
use InputType\DistributedItemFilterInputType;
use InputType\DistributedItemOrderInputType;
use Repository\DistributedItemRepository;
use Request\Pagination;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Utils\DistributedSummaryTransformData;
use Utils\ExportTableServiceInterface;

class DistributedItemController extends AbstractController
{
    public function __construct(private readonly ManagerRegistry $managerRegistry, private readonly Countries $countries, private readonly DistributedItemRepository $distributedItemRepository, private readonly DistributedSummaryTransformData $distributedSummaryTransformData, private readonly ExportTableServiceInterface $exportTableService)
    {
    }

    #[Rest\Get('/web-app/v1/beneficiaries/{id}/distributed-items')]
    #[ParamConverter('beneficiary')]
    public function listByBeneficiary(Beneficiary $beneficiary): JsonResponse
    {
        $data = $this->managerRegistry->getRepository(DistributedItem::class)
            ->findByBeneficiary($beneficiary);

        return $this->json($data);
    }

    #[Rest\Get('/web-app/v1/households/{id}/distributed-items')]
    #[ParamConverter('household')]
    public function listByHousehold(Household $household): JsonResponse
    {
        $data = $this->managerRegistry->getRepository(DistributedItem::class)
            ->findByHousehold($household);

        return $this->json($data);
    }

    #[Rest\Get('/web-app/v1/distributed-items')]
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

    #[Rest\Get('/web-app/v1/distributed-items/exports')]
    public function summaryExports(Request $request, DistributedItemFilterInputType $inputType): StreamedResponse
    {
        if (!$request->headers->has('country')) {
            throw $this->createNotFoundException('Missing header attribute country');
        }

        $country = $this->countries->getCountry($request->headers->get('country'));
        $distributedItems = $this->distributedItemRepository->findByParams($country->getIso3(), $inputType);
        $exportableTable = $this->distributedSummaryTransformData->transformData($distributedItems, $country);

        return $this->exportTableService->export($exportableTable, 'summary', $request->get('type'), false, true);
        ;
    }
}
