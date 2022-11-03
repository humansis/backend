<?php

declare(strict_types=1);

namespace Controller\VendorApp;

use Doctrine\Persistence\ManagerRegistry;
use FOS\RestBundle\Controller\Annotations as Rest;
use Component\Smartcard\SmartcardDepositService;
use Entity\SynchronizationBatch;
use Enum\SourceType;
use Enum\SynchronizationBatchValidationType;
use Symfony\Component\HttpFoundation\Request;
use InputType\SynchronizationBatch as API;
use Symfony\Component\HttpFoundation\Response;

class SynchronizationBatchController extends AbstractVendorAppController
{
    public function __construct(private readonly ManagerRegistry $managerRegistry)
    {
    }
    /**
     * @Rest\Post("/vendor-app/v1/syncs/deposit")
     *
     *
     */
    public function create(Request $request, SmartcardDepositService $depositService): Response
    {
        $sync = new SynchronizationBatch\Deposits($request->request->all());
        $sync->setSource(SourceType::VENDOR_APP);
        $sync->setCreatedBy($this->getUser());
        $this->managerRegistry->getManager()->persist($sync);
        $this->managerRegistry->getManager()->flush();

        $depositService->validateSync($sync);

        $response = new Response();
        $response->setStatusCode(Response::HTTP_NO_CONTENT);

        return $response;
    }
}
