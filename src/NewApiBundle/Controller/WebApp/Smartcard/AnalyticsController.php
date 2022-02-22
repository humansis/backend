<?php declare(strict_types=1);

namespace NewApiBundle\Controller\WebApp\Smartcard;

use BeneficiaryBundle\Entity\Beneficiary;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\Component\Smartcard\Analytics\Event;
use NewApiBundle\Component\Smartcard\Analytics\EventCollector;
use NewApiBundle\Controller\WebApp\AbstractWebAppController;
use NewApiBundle\Entity\SynchronizationBatch;
use NewApiBundle\Enum\SourceType;
use Symfony\Component\HttpFoundation\JsonResponse;
use VoucherBundle\Entity\Smartcard;
use VoucherBundle\Entity\SmartcardRedemptionBatch;
use VoucherBundle\Entity\Vendor;
use VoucherBundle\Repository\SmartcardPurchaseRepository;
use VoucherBundle\Repository\SmartcardRedemptionBatchRepository;
use VoucherBundle\Repository\SmartcardRepository;

/**
 * properties *Name are there only for reason of nonexistent frontend
 * should be removed after FE part will be done
 */
class AnalyticsController extends AbstractWebAppController
{
    /**
     * @Rest\Get("/web-app/v1/smartcard/analytics/beneficiary/{id}")
     *
     * @param Beneficiary                 $beneficiary
     * @param SmartcardPurchaseRepository $purchaseRepository
     *
     * @return JsonResponse
     */
    public function beneficiary(Beneficiary $beneficiary, SmartcardPurchaseRepository $purchaseRepository): JsonResponse
    {
        $collector = new EventCollector();
        $collector->add(new Event('beneficiary', 'updated', $beneficiary->getUpdatedOn()));
        foreach ($beneficiary->getDistributionBeneficiaries() as $assistanceBeneficiary) {
            $assistance = $assistanceBeneficiary->getAssistance();
            $collector->add(new Event('assistance', 'started', $assistance->getDateDistribution(), [
                'assistanceId' => $assistance->getId(),
                'assistanceName' => $assistance->getName(),
                'assistance_beneficiaryId' => $assistanceBeneficiary->getId(),
            ]));
            if ($assistance->getDateExpiration()) {
                $collector->add(new Event('assistance', 'expired', $assistance->getDateExpiration(), [
                    'assistanceId' => $assistance->getId(),
                    'assistanceName' => $assistance->getName(),
                    'assistance_beneficiaryId' => $assistanceBeneficiary->getId(),
                ]));
            }

            foreach ($assistanceBeneficiary->getReliefPackages() as $reliefPackage) {
                $collector->add(new Event('assistance', 'set to distribute', $reliefPackage->getCreatedAt(), [
                    'assistanceId' => $assistance->getId(),
                    'assistanceName' => $assistance->getName(),
                    'relief_package' => $reliefPackage->getId(),
                    'assistance_beneficiaryId' => $assistanceBeneficiary->getId(),
                ]));
            }

            foreach ($assistanceBeneficiary->getSmartcardDeposits() as $deposit) {
                $collector->add(new Event('deposit', 'sync', $deposit->getCreatedAt(), [
                    'depositId' => $deposit->getId(),
                ]));
                $collector->add(new Event('deposit', 'got money', $deposit->getDistributedAt(), [
                    'value' => $deposit->getValue(),
                    'assistanceId' => $assistance->getId(),
                    'assistanceName' => $assistance->getName(),
                    'depositId' => $deposit->getId(),
                    'smartcardId' => $deposit->getSmartcard()->getId(),
                    'smartcardSerialNumber' => $deposit->getSmartcard()->getSerialNumber(),
                ]));
            }
        }

        foreach ($purchaseRepository->findByBeneficiary($beneficiary) as $purchase) {
            $collector->add(new Event('purchase', 'made', $purchase->getCreatedAt(), [
                'value' => $purchase->getRecordsValue().' '.$purchase->getCurrency(),
                'assistanceId' => $purchase->getAssistance() ? $purchase->getAssistance()->getId() : null,
                'assistanceName' => $purchase->getAssistance() ? $purchase->getAssistance()->getName() : null,
                'purchaseId' => $purchase->getId(),
                'smartcardId' => $purchase->getSmartcard()->getId(),
                'smartcardSerialNumber' => $purchase->getSmartcard()->getSerialNumber(),
                'vendorId' => $purchase->getVendor()->getId(),
                'vendorName' => $purchase->getVendor()->getName(),
            ]));
            if ($purchase->getRedemptionBatch()) {
                $collector->add(new Event('purchase', 'invoiced', $purchase->getRedeemedAt(), [
                    'purchaseId' => $purchase->getId(),
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
        $collector = new EventCollector();
        $this->collectSmartcard($collector, $smartcard);
        return $this->json($collector->getSortedEvents());
    }

    /**
     * @Rest\Get("/web-app/v1/smartcard/analytics/smartcards/{serialNumber}")
     *
     * @param string $serialNumber
     *
     * @return JsonResponse
     */
    public function smartcardBySerialNumber(string $serialNumber, SmartcardRepository $smartcardRepository): JsonResponse
    {
        $smartcards = $smartcardRepository->findBy(['serialNumber'=>$serialNumber]);
        $collector = new EventCollector();
        foreach ($smartcards as $smartcard) {
            $this->collectSmartcard($collector, $smartcard);
        }
        return $this->json($collector->getSortedEvents());
    }

    private function collectSmartcard(EventCollector $collector, Smartcard $smartcard): void
    {
        foreach ($smartcard->getDeposites() as $deposit) {
            $reliefPackage = $deposit->getReliefPackage();
            $assistanceBeneficiary = $reliefPackage->getAssistanceBeneficiary();
            $assistance = $assistanceBeneficiary->getAssistance();

            $collector->add(new Event('assistance', 'set to distribute', $reliefPackage->getCreatedAt(), [
                'assistanceId' => $assistance->getId(),
                'assistanceName' => $assistance->getName(),
                'relief_package' => $reliefPackage->getId(),
                'assistance_beneficiaryId' => $assistanceBeneficiary->getId(),
            ]));

            $collector->add(new Event('assistance', 'started', $assistance->getDateDistribution(), [
                'assistanceId' => $assistance->getId(),
                'assistanceName' => $assistance->getName(),
                'assistance_beneficiaryId' => $assistanceBeneficiary->getId(),
            ]));
            if ($assistance->getDateExpiration()) {
                $collector->add(new Event('assistance', 'expired', $assistance->getDateExpiration(), [
                    'assistanceId' => $assistance->getId(),
                    'assistanceName' => $assistance->getName(),
                    'assistance_beneficiaryId' => $assistanceBeneficiary->getId(),
                ]));
            }

            $collector->add(new Event('deposit', 'sync', $deposit->getCreatedAt(), [
                'depositId' => $deposit->getId(),
            ]));

            $collector->add(new Event('deposit', 'got money', $deposit->getDistributedAt(), [
                'value' => $deposit->getValue(),
                'assistanceId' => $assistance->getId(),
                'assistanceName' => $assistance->getName(),
                'depositId' => $deposit->getId(),
                'smartcardId' => $smartcard->getId(),
                'smartcardSerialNumber' => $deposit->getSmartcard()->getSerialNumber(),
            ]));
        }

        foreach ($smartcard->getPurchases() as $purchase) {
            $collector->add(new Event('purchase', 'made', $purchase->getCreatedAt(), [
                'value' => $purchase->getRecordsValue().' '.$purchase->getCurrency(),
                'assistanceId' => $purchase->getAssistance() ? $purchase->getAssistance()->getId() : null,
                'assistanceName' => $purchase->getAssistance() ? $purchase->getAssistance()->getName() : null,
                'purchaseId' => $purchase->getId(),
                'smartcardId' => $purchase->getSmartcard()->getId(),
                'smartcardSerialNumber' => $purchase->getSmartcard()->getSerialNumber(),
                'vendorId' => $purchase->getVendor()->getId(),
                'vendorName' => $purchase->getVendor()->getName(),
            ]));
            if ($purchase->getRedemptionBatch()) {
                $collector->add(new Event('purchase', 'invoiced', $purchase->getRedeemedAt(), [
                    'purchaseId' => $purchase->getId(),
                ]));
            }
        }
    }

    /**
     * @Rest\Get("/web-app/v1/smartcard/analytics/vendor/{id}")
     *
     * @param Vendor $vendor
     *
     * @return JsonResponse
     */
    public function vendor(
        Vendor $vendor,
        SmartcardPurchaseRepository $purchaseRepository
    ): JsonResponse
    {
        $redemptionBatchRepository = $this->getDoctrine()->getRepository(SmartcardRedemptionBatch::class);
        $depositSyncRepository = $this->getDoctrine()->getRepository(SynchronizationBatch\Deposits::class);
        $purchaseSyncRepository = $this->getDoctrine()->getRepository(SynchronizationBatch\Purchases::class);

        $collector = new EventCollector();
        foreach ($purchaseRepository->findBy(['vendor'=>$vendor]) as $purchase) {
            $collector->add(new Event('purchase', 'made', $purchase->getCreatedAt(), [
                'value' => $purchase->getRecordsValue().' '.$purchase->getCurrency(),
                'assistanceId' => $purchase->getAssistance() ? $purchase->getAssistance()->getId() : null,
                'assistanceName' => $purchase->getAssistance() ? $purchase->getAssistance()->getName() : null,
                'purchaseId' => $purchase->getId(),
                'smartcardId' => $purchase->getSmartcard()->getId(),
                'smartcardSerialNumber' => $purchase->getSmartcard()->getSerialNumber(),
                'vendorId' => $purchase->getVendor()->getId(),
                'vendorName' => $purchase->getVendor()->getName(),
            ]));
        }
        foreach ($redemptionBatchRepository->findByVendor($vendor) as $invoice) {
            $collector->add(new Event('invoice', 'made', $invoice->getRedeemedAt(), [
                'value' => $invoice->getValue().' '.$invoice->getCurrency(),
                'projectId' => $invoice->getProject() ? $invoice->getProject()->getId() : null,
                'projectName' => $invoice->getProject() ? $invoice->getProject()->getName() : null,
                'accountantId' => $invoice->getRedeemedBy()->getId(),
                'accountantName' => $invoice->getRedeemedBy()->getUsername(),
                'vendorId' => $invoice->getVendor()->getId(),
                'vendorName' => $invoice->getVendor()->getName(),
            ]));
        }
        foreach ($depositSyncRepository->findBy(['createdBy' => $vendor]) as $sync) {
            $collector->add(new Event('deposit', 'sync uploaded', $sync->getCreatedAt(), [
                'syncId' => $sync->getId(),
                'source' => $sync->getSource(),
                'vendorId' => $vendor->getId(),
                'vendorName' => $vendor->getName(),
            ]));
            $collector->add(new Event('deposit', 'sync validated', $sync->getValidatedAt(), [
                'syncId' => $sync->getId(),
                'source' => $sync->getSource(),
                'vendorId' => $vendor->getId(),
                'vendorName' => $vendor->getName(),
            ]));
        }
        foreach ($purchaseSyncRepository->findBy(['createdBy' => $vendor]) as $sync) {
            $collector->add(new Event('purchase', 'sync uploaded', $sync->getCreatedAt(), [
                'syncId' => $sync->getId(),
                'source' => $sync->getSource(),
                'vendorId' => $vendor->getId(),
                'vendorName' => $vendor->getName(),
            ]));
            $collector->add(new Event('purchase', 'sync validated', $sync->getValidatedAt(), [
                'syncId' => $sync->getId(),
                'source' => $sync->getSource(),
                'vendorId' => $vendor->getId(),
                'vendorName' => $vendor->getName(),
            ]));
        }
        return $this->json($collector->getSortedEvents());
    }
}
