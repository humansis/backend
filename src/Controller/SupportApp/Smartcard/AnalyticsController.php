<?php

declare(strict_types=1);

namespace Controller\SupportApp\Smartcard;

use Entity\Beneficiary;
use Pagination\Paginator;
use FOS\RestBundle\Controller\Annotations as Rest;
use Component\Smartcard\Analytics\EventService;
use Controller\SupportApp\AbstractSupportAppController;
use phpDocumentor\Reflection\Types\This;
use Symfony\Component\HttpFoundation\JsonResponse;
use Entity\SmartcardBeneficiary;
use Entity\Vendor;

class AnalyticsController extends AbstractSupportAppController
{
    #[Rest\Get('/support-app/v1/smartcard-analytics/beneficiaries/{id}')]
    public function beneficiary(Beneficiary $beneficiary, EventService $eventService): JsonResponse
    {
        return $this->json(new Paginator($eventService->getBeneficiaryEvents($beneficiary)));
    }

    #[Rest\Get('/support-app/v1/smartcard-analytics/smartcard/{id}')]
    public function smartcardById(SmartcardBeneficiary $smartcardBeneficiary, EventService $eventService): JsonResponse
    {
        return $this->json(new Paginator($eventService->getSmartcardEvents($smartcardBeneficiary)));
    }

    #[Rest\Get('/support-app/v1/smartcard-analytics/smartcards/{serialNumber}')]
    public function smartcardBySerialNumber(string $serialNumber, EventService $eventService): JsonResponse
    {
        return $this->json(new Paginator($eventService->getSmartcardsEvents($serialNumber)));
    }

    #[Rest\Get('/support-app/v1/smartcard-analytics/vendors/{id}')]
    public function vendor(Vendor $vendor, EventService $eventService): JsonResponse
    {
        return $this->json(new Paginator($eventService->getVendorEvents($vendor)));
    }
}
