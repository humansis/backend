<?php declare(strict_types=1);

namespace NewApiBundle\Controller\WebApp\Smartcard;

use BeneficiaryBundle\Entity\Beneficiary;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\Controller\WebApp\AbstractWebAppController;
use Symfony\Component\HttpFoundation\JsonResponse;
use VoucherBundle\Entity\Smartcard;
use VoucherBundle\Entity\Vendor;

class AnalyticsController extends AbstractWebAppController
{
    /**
     * @Rest\Get("/web-app/v1/smartcard/analytics/beneficiary/{id}")
     *
     * @param Beneficiary $beneficiary
     *
     * @return JsonResponse
     */
    public function beneficiary(Beneficiary $beneficiary): JsonResponse
    {
        return $this->json([]);
    }

    /**
     * @Rest\Get("/web-app/v1/smartcard/analytics/smartcard/{id}")
     *
     * @param Smartcard $smartcard
     *
     * @return JsonResponse
     */
    public function smartcardById(Smartcard $smartcard): JsonResponse
    {
        return $this->json([]);
    }

    /**
     * @Rest\Get("/web-app/v1/smartcard/analytics/smartcards/{serialNumber}")
     *
     * @param string $serialNumber
     *
     * @return JsonResponse
     */
    public function smartcardBySerialNumber(string $serialNumber): JsonResponse
    {
        return $this->json([]);
    }

    /**
     * @Rest\Get("/web-app/v1/smartcard/analytics/vendor/{id}")
     *
     * @param Vendor $vendor
     *
     * @return JsonResponse
     */
    public function vendor(Vendor $vendor): JsonResponse
    {
        return $this->json([]);
    }
}
