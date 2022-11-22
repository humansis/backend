<?php

declare(strict_types=1);

namespace Controller\VendorApp;

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
    /**
     * @Rest\Post("/vendor-app/v1/syncs/deposit")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function create(Request $request, SmartcardDepositService $depositService): Response
    {
        $sync = new SynchronizationBatch\Deposits($request->request->all());
        $sync->setSource(SourceType::VENDOR_APP);
        $sync->setCreatedBy($this->getUser());
        $this->getDoctrine()->getManager()->persist($sync);
        $this->getDoctrine()->getManager()->flush();

        $depositService->validateSync($sync);

        $response = new Response();
        $response->setStatusCode(Response::HTTP_NO_CONTENT);

        return $response;
    }
}
