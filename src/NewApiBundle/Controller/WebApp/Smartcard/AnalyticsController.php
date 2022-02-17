<?php declare(strict_types=1);

namespace NewApiBundle\Controller\WebApp\Smartcard;

use BeneficiaryBundle\Entity\Beneficiary;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\Component\Smartcard\Analytics\Event;
use NewApiBundle\Component\Smartcard\Analytics\EventCollector;
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
        $collector = new EventCollector();
        $collector->add(new Event('beneficiary', 'updated', $beneficiary->getUpdatedOn()));
        foreach ($beneficiary->getDistributionBeneficiaries() as $assistanceBeneficiary) {
            $assistance = $assistanceBeneficiary->getAssistance();
            $collector->add(new Event('assistance', 'started', $assistance->getDateDistribution(), [
                'assistance_id' => $assistance->getId(),
                'assistance_name' => $assistance->getName(),
                'assistance_beneficiary_id' => $assistanceBeneficiary->getId(),
            ]));
            if ($assistance->getDateExpiration()) {
                $collector->add(new Event('assistance', 'expired', $assistance->getDateExpiration(), [
                    'assistance_id' => $assistance->getId(),
                    'assistance_name' => $assistance->getName(),
                    'assistance_beneficiary_id' => $assistanceBeneficiary->getId(),
                ]));
            }

            foreach ($assistanceBeneficiary->getReliefPackages() as $reliefPackage) {
                $collector->add(new Event('assistance', 'set to distribute', $reliefPackage->getCreatedAt(), [
                    'assistance_id' => $assistance->getId(),
                    'assistance_name' => $assistance->getName(),
                    'relief_package' => $reliefPackage->getId(),
                    'assistance_beneficiary_id' => $assistanceBeneficiary->getId(),
                ]));
            }

            foreach ($assistanceBeneficiary->getSmartcardDeposits() as $deposit) {
                $collector->add(new Event('deposit', 'sync', $deposit->getCreatedAt(), [
                    'assistance_id' => $assistance->getId(),
                    'assistance_name' => $assistance->getName(),
                    'deposit_id' => $deposit->getId(),
                    'smartcard_id' => $deposit->getSmartcard()->getId(),
                    'smartcard_serialNumber' => $deposit->getSmartcard()->getSerialNumber(),
                ]));
                $collector->add(new Event('deposit', 'got money', $deposit->getDistributedAt(), [
                    'assistance_id'=>$assistance->getId(),
                    'assistance_name' => $assistance->getName(),
                    'deposit_id' => $deposit->getId(),
                    'smartcard_id' => $deposit->getSmartcard()->getId(),
                    'smartcard_serialNumber' => $deposit->getSmartcard()->getSerialNumber(),
                ]));
            }
        }

        return $this->json($collector->getSortedEvents());
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
