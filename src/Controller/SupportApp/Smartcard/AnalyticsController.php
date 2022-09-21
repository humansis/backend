<?php declare(strict_types=1);

namespace Controller\SupportApp\Smartcard;

use BeneficiaryBundle\Entity\Beneficiary;
use CommonBundle\Pagination\Paginator;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use Component\Smartcard\Analytics\EventService;
use Controller\SupportApp\AbstractSupportAppController;
use phpDocumentor\Reflection\Types\This;
use Symfony\Component\HttpFoundation\JsonResponse;
use VoucherBundle\Entity\Smartcard;
use VoucherBundle\Entity\Vendor;
use VoucherBundle\Repository\SmartcardRepository;

class AnalyticsController extends AbstractSupportAppController
{

    /**
     * @Rest\Get("/support-app/v1/smartcard-analytics/beneficiaries/{id}")
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
     * @Rest\Get("/support-app/v1/smartcard-analytics/smartcard/{id}")
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
     * @Rest\Get("/support-app/v1/smartcard-analytics/smartcards/{serialNumber}")
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
     * @Rest\Get("/support-app/v1/smartcard-analytics/vendors/{id}")
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
