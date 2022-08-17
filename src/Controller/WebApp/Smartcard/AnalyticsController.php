<?php declare(strict_types=1);

namespace Controller\WebApp\Smartcard;

use Entity\Beneficiary;
use Pagination\Paginator;
use FOS\RestBundle\Controller\Annotations as Rest;
use Component\Smartcard\Analytics\EventService;
use Controller\WebApp\AbstractWebAppController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Entity\Smartcard;
use Entity\Vendor;

/**
 * properties *Name are there only for reason of nonexistent frontend
 * should be removed after FE part will be done
 */
class AnalyticsController extends AbstractWebAppController
{
    /**
     * @Rest\Get("/web-app/v1/smartcard/analytics/beneficiary/{id}")
     *
     * @param Beneficiary  $beneficiary
     * @param EventService $eventService
     *
     * @return JsonResponse
     */
    public function beneficiary(Beneficiary $beneficiary, EventService $eventService): JsonResponse
    {
        return $this->json(new Paginator($eventService->getBeneficiaryEvents($beneficiary)));
    }

    /**
     * @Rest\Get("/web-app/v1/smartcard/analytics/smartcard/{id}")
     *
     * @param Smartcard    $smartcard
     * @param EventService $eventService
     *
     * @return JsonResponse
     */
    public function smartcardById(Smartcard $smartcard, EventService $eventService): JsonResponse
    {
        return $this->json(new Paginator($eventService->getSmartcardEvents($smartcard)));
    }

    /**
     * @Rest\Get("/web-app/v1/smartcard/analytics/smartcards/{serialNumber}")
     *
     * @param string       $serialNumber
     * @param EventService $eventService
     *
     * @return JsonResponse
     */
    public function smartcardBySerialNumber(string $serialNumber, EventService $eventService): JsonResponse
    {
        return $this->json(new Paginator($eventService->getSmartcardsEvents($serialNumber)));
    }

    /**
     * @Rest\Get("/web-app/v1/smartcard/analytics/vendor/{id}")
     *
     * @param Vendor       $vendor
     * @param EventService $eventService
     *
     * @return JsonResponse
     */
    public function vendor(Vendor $vendor, EventService $eventService): JsonResponse
    {
        return $this->json(new Paginator($eventService->getVendorEvents($vendor)));
    }
}
