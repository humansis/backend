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
                    'deposit_id' => $deposit->getId(),
                ]));
                $collector->add(new Event('deposit', 'got money', $deposit->getDistributedAt(), [
                    'value' => $deposit->getValue(),
                    'assistance_id' => $assistance->getId(),
                    'assistance_name' => $assistance->getName(),
                    'deposit_id' => $deposit->getId(),
                    'smartcard_id' => $deposit->getSmartcard()->getId(),
                    'smartcard_serialNumber' => $deposit->getSmartcard()->getSerialNumber(),
                ]));
            }
        }

        foreach ($purchaseRepository->findByBeneficiary($beneficiary) as $purchase) {
            $collector->add(new Event('purchase', 'made', $purchase->getCreatedAt(), [
                'value' => $purchase->getRecordsValue().' '.$purchase->getCurrency(),
                'assistance_id' => $purchase->getAssistance() ? $purchase->getAssistance()->getId() : null,
                'assistance_name' => $purchase->getAssistance() ? $purchase->getAssistance()->getName() : null,
                'purchase_id' => $purchase->getId(),
                'smartcard_id' => $purchase->getSmartcard()->getId(),
                'smartcard_serialNumber' => $purchase->getSmartcard()->getSerialNumber(),
                'vendor_id' => $purchase->getVendor()->getId(),
                'vendor_name' => $purchase->getVendor()->getName(),
            ]));
            if ($purchase->getRedemptionBatch()) {
                $collector->add(new Event('purchase', 'invoiced', $purchase->getRedeemedAt(), [
                    'purchase_id' => $purchase->getId(),
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
                'assistance_id' => $assistance->getId(),
                'assistance_name' => $assistance->getName(),
                'relief_package' => $reliefPackage->getId(),
                'assistance_beneficiary_id' => $assistanceBeneficiary->getId(),
            ]));

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

            $collector->add(new Event('deposit', 'sync', $deposit->getCreatedAt(), [
                'deposit_id' => $deposit->getId(),
            ]));

            $collector->add(new Event('deposit', 'got money', $deposit->getDistributedAt(), [
                'value' => $deposit->getValue(),
                'assistance_id' => $assistance->getId(),
                'assistance_name' => $assistance->getName(),
                'deposit_id' => $deposit->getId(),
                'smartcard_id' => $smartcard->getId(),
                'smartcard_serialNumber' => $deposit->getSmartcard()->getSerialNumber(),
            ]));
        }

        foreach ($smartcard->getPurchases() as $purchase) {
            $collector->add(new Event('purchase', 'made', $purchase->getCreatedAt(), [
                'value' => $purchase->getRecordsValue().' '.$purchase->getCurrency(),
                'assistance_id' => $purchase->getAssistance() ? $purchase->getAssistance()->getId() : null,
                'assistance_name' => $purchase->getAssistance() ? $purchase->getAssistance()->getName() : null,
                'purchase_id' => $purchase->getId(),
                'smartcard_id' => $purchase->getSmartcard()->getId(),
                'smartcard_serialNumber' => $purchase->getSmartcard()->getSerialNumber(),
                'vendor_id' => $purchase->getVendor()->getId(),
                'vendor_name' => $purchase->getVendor()->getName(),
            ]));
            if ($purchase->getRedemptionBatch()) {
                $collector->add(new Event('purchase', 'invoiced', $purchase->getRedeemedAt(), [
                    'purchase_id' => $purchase->getId(),
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
                'assistance_id' => $purchase->getAssistance() ? $purchase->getAssistance()->getId() : null,
                'assistance_name' => $purchase->getAssistance() ? $purchase->getAssistance()->getName() : null,
                'purchase_id' => $purchase->getId(),
                'smartcard_id' => $purchase->getSmartcard()->getId(),
                'smartcard_serialNumber' => $purchase->getSmartcard()->getSerialNumber(),
                'vendor_id' => $purchase->getVendor()->getId(),
                'vendor_name' => $purchase->getVendor()->getName(),
            ]));
        }
        foreach ($redemptionBatchRepository->findByVendor($vendor) as $invoice) {
            $collector->add(new Event('invoice', 'made', $invoice->getRedeemedAt(), [
                'value' => $invoice->getValue().' '.$invoice->getCurrency(),
                'project_id' => $invoice->getProject() ? $invoice->getProject()->getId() : null,
                'project_name' => $invoice->getProject() ? $invoice->getProject()->getName() : null,
                'accountant_id' => $invoice->getRedeemedBy()->getId(),
                'accountant_name' => $invoice->getRedeemedBy()->getUsername(),
                'vendor_id' => $invoice->getVendor()->getId(),
                'vendor_name' => $invoice->getVendor()->getName(),
            ]));
        }
        foreach ($depositSyncRepository->findBy(['createdBy' => $vendor]) as $sync) {
            $collector->add(new Event('deposit sync', 'made', $sync->getCreatedAt(), [
                'sync_id' => $sync->getId(),
                'source' => $sync->getSource(),
                'vendor_id' => $vendor->getId(),
                'vendor_name' => $vendor->getName(),
            ]));
            $collector->add(new Event('deposit sync', 'validated', $sync->getValidatedAt(), [
                'sync_id' => $sync->getId(),
                'source' => $sync->getSource(),
                'vendor_id' => $vendor->getId(),
                'vendor_name' => $vendor->getName(),
            ]));
        }
        foreach ($purchaseSyncRepository->findBy(['createdBy' => $vendor]) as $sync) {
            $collector->add(new Event('purchase sync', 'made', $sync->getCreatedAt(), [
                'sync_id' => $sync->getId(),
                'source' => $sync->getSource(),
                'vendor_id' => $vendor->getId(),
                'vendor_name' => $vendor->getName(),
            ]));
            $collector->add(new Event('purchase sync', 'validated', $sync->getValidatedAt(), [
                'sync_id' => $sync->getId(),
                'source' => $sync->getSource(),
                'vendor_id' => $vendor->getId(),
                'vendor_name' => $vendor->getName(),
            ]));
        }
        return $this->json($collector->getSortedEvents());
    }
}
