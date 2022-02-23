<?php declare(strict_types=1);

namespace NewApiBundle\Component\Smartcard\Analytics;

use BeneficiaryBundle\Entity\Beneficiary;
use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\Entity\SynchronizationBatch;
use NewApiBundle\Repository\SynchronizationBatchRepository;
use VoucherBundle\Entity\Smartcard;
use VoucherBundle\Entity\SmartcardPurchase;
use VoucherBundle\Entity\SmartcardRedemptionBatch;
use VoucherBundle\Entity\Vendor;
use VoucherBundle\Repository\SmartcardPurchaseRepository;
use VoucherBundle\Repository\SmartcardRedemptionBatchRepository;
use VoucherBundle\Repository\SmartcardRepository;

class EventService
{
    /** @var SmartcardPurchaseRepository */
    private $purchaseRepository;
    /** @var SmartcardRepository $smartcardRepository */
    private $smartcardRepository;
    /** @var SynchronizationBatchRepository */
    private $purchaseSyncRepository;
    /** @var SynchronizationBatchRepository */
    private $depositSyncRepository;
    /** @var SmartcardRedemptionBatchRepository */
    private $redemptionBatchRepository;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager
    ) {
        $this->purchaseRepository = $entityManager->getRepository(SmartcardPurchase::class);
        $this->smartcardRepository = $entityManager->getRepository(Smartcard::class);
        $this->depositSyncRepository = $entityManager->getRepository(SynchronizationBatch\Deposits::class);
        $this->purchaseSyncRepository = $entityManager->getRepository(SynchronizationBatch\Purchases::class);
        $this->redemptionBatchRepository = $entityManager->getRepository(SmartcardRedemptionBatch::class);
    }

    public function getBeneficiaryEvents(Beneficiary $beneficiary): array
    {
        $collector = new EventCollector();
        $collector->add(new Event('beneficiary', 'updated', $beneficiary->getUpdatedOn()));
        foreach ($beneficiary->getDistributionBeneficiaries() as $assistanceBeneficiary) {
            $assistance = $assistanceBeneficiary->getAssistance();
            $collector->add(new Event('assistance', 'started', $assistance->getDateDistribution(), [], [
                'assistanceId' => $assistance->getId(),
                'assistanceName' => $assistance->getName(),
                'assistanceBeneficiaryId' => $assistanceBeneficiary->getId(),
            ]));
            if ($assistance->getDateExpiration()) {
                $collector->add(new Event('assistance', 'expired', $assistance->getDateExpiration(), [], [
                    'assistanceId' => $assistance->getId(),
                    'assistanceName' => $assistance->getName(),
                    'assistanceBeneficiaryId' => $assistanceBeneficiary->getId(),
                ]));
            }

            foreach ($assistanceBeneficiary->getReliefPackages() as $reliefPackage) {
                $collector->add(new Event('assistance', 'set to distribute', $reliefPackage->getCreatedAt(), [], [
                    'assistanceId' => $assistance->getId(),
                    'assistanceName' => $assistance->getName(),
                    'reliefPackageId' => $reliefPackage->getId(),
                    'assistanceBeneficiaryId' => $assistanceBeneficiary->getId(),
                ]));
            }

            foreach ($assistanceBeneficiary->getSmartcardDeposits() as $deposit) {
                $collector->add(new Event('deposit', 'sync', $deposit->getCreatedAt(), [], [
                    'depositId' => $deposit->getId(),
                ]));
                $collector->add(new Event('deposit', 'got money', $deposit->getDistributedAt(), [], [
                    'value' => $deposit->getValue(),
                    'assistanceId' => $assistance->getId(),
                    'assistanceName' => $assistance->getName(),
                    'depositId' => $deposit->getId(),
                    'smartcardId' => $deposit->getSmartcard()->getId(),
                    'smartcardSerialNumber' => $deposit->getSmartcard()->getSerialNumber(),
                ]));
            }
        }

        foreach ($this->purchaseRepository->findByBeneficiary($beneficiary) as $purchase) {
            $collector->add(new Event('purchase', 'made', $purchase->getCreatedAt(), [], [
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
                $collector->add(new Event('purchase', 'invoiced', $purchase->getRedeemedAt(), [], [
                    'purchaseId' => $purchase->getId(),
                ]));
            }
        }

        return $collector->getSortedEvents();
    }

    public function getSmartcardEvents(Smartcard $smartcard): array
    {
        $collector = new EventCollector();
        $this->collectSmartcard($collector, $smartcard);
        return $collector->getSortedEvents();
    }

    public function getSmartcardsEvents(string $serialNumber): array
    {
        $smartcards = $this->smartcardRepository->findBy(['serialNumber'=>$serialNumber]);
        $collector = new EventCollector();
        foreach ($smartcards as $smartcard) {
            $this->collectSmartcard($collector, $smartcard);
        }
        return $collector->getSortedEvents();
    }

    private function collectSmartcard(EventCollector $collector, Smartcard $smartcard): void
    {
        foreach ($smartcard->getDeposites() as $deposit) {
            $reliefPackage = $deposit->getReliefPackage();
            $assistanceBeneficiary = $reliefPackage->getAssistanceBeneficiary();
            $assistance = $assistanceBeneficiary->getAssistance();

            $collector->add(new Event('assistance', 'set to distribute', $reliefPackage->getCreatedAt(), [], [
                'assistanceId' => $assistance->getId(),
                'assistanceName' => $assistance->getName(),
                'reliefPackageId' => $reliefPackage->getId(),
                'assistanceBeneficiaryId' => $assistanceBeneficiary->getId(),
            ]));

            $collector->add(new Event('assistance', 'started', $assistance->getDateDistribution(), [], [
                'assistanceId' => $assistance->getId(),
                'assistanceName' => $assistance->getName(),
                'assistanceBeneficiaryId' => $assistanceBeneficiary->getId(),
            ]));
            if ($assistance->getDateExpiration()) {
                $collector->add(new Event('assistance', 'expired', $assistance->getDateExpiration(), [], [
                    'assistanceId' => $assistance->getId(),
                    'assistanceName' => $assistance->getName(),
                    'assistanceBeneficiaryId' => $assistanceBeneficiary->getId(),
                ]));
            }

            $collector->add(new Event('deposit', 'sync', $deposit->getCreatedAt(), [], [
                'depositId' => $deposit->getId(),
            ]));

            $collector->add(new Event('deposit', 'got money', $deposit->getDistributedAt(), [], [
                'value' => $deposit->getValue(),
                'assistanceId' => $assistance->getId(),
                'assistanceName' => $assistance->getName(),
                'depositId' => $deposit->getId(),
                'smartcardId' => $smartcard->getId(),
                'smartcardSerialNumber' => $deposit->getSmartcard()->getSerialNumber(),
            ]));
        }

        foreach ($smartcard->getPurchases() as $purchase) {
            $collector->add(new Event('purchase', 'made', $purchase->getCreatedAt(), [], [
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
                $collector->add(new Event('purchase', 'invoiced', $purchase->getRedeemedAt(), [], [
                    'purchaseId' => $purchase->getId(),
                ]));
            }
        }
    }

    public function getVendorEvents(Vendor $vendor): array
    {
        $collector = new EventCollector();
        foreach ($this->purchaseRepository->findBy(['vendor'=>$vendor]) as $purchase) {
            $collector->add(new Event('purchase', 'made', $purchase->getCreatedAt(), [], [
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
        foreach ($this->redemptionBatchRepository->findByVendor($vendor) as $invoice) {
            $collector->add(new Event('invoice', 'made', $invoice->getRedeemedAt(), [], [
                'value' => $invoice->getValue().' '.$invoice->getCurrency(),
                'projectId' => $invoice->getProject() ? $invoice->getProject()->getId() : null,
                'projectName' => $invoice->getProject() ? $invoice->getProject()->getName() : null,
                'accountantId' => $invoice->getRedeemedBy()->getId(),
                'accountantName' => $invoice->getRedeemedBy()->getUsername(),
                'vendorId' => $invoice->getVendor()->getId(),
                'vendorName' => $invoice->getVendor()->getName(),
            ]));
        }
        foreach ($this->depositSyncRepository->findBy(['createdBy' => $vendor]) as $sync) {
            $collector->add(new Event('deposit', 'sync uploaded', $sync->getCreatedAt(), [], [
                'syncId' => $sync->getId(),
                'source' => $sync->getSource(),
                'vendorId' => $vendor->getId(),
                'vendorName' => $vendor->getName(),
            ]));
            $collector->add(new Event('deposit', 'sync validated', $sync->getValidatedAt(), [], [
                'syncId' => $sync->getId(),
                'source' => $sync->getSource(),
                'vendorId' => $vendor->getId(),
                'vendorName' => $vendor->getName(),
            ]));
        }
        foreach ($this->purchaseSyncRepository->findBy(['createdBy' => $vendor]) as $sync) {
            $collector->add(new Event('purchase', 'sync uploaded', $sync->getCreatedAt(), [], [
                'syncId' => $sync->getId(),
                'source' => $sync->getSource(),
                'vendorId' => $vendor->getId(),
                'vendorName' => $vendor->getName(),
            ]));
            $collector->add(new Event('purchase', 'sync validated', $sync->getValidatedAt(), [], [
                'syncId' => $sync->getId(),
                'source' => $sync->getSource(),
                'vendorId' => $vendor->getId(),
                'vendorName' => $vendor->getName(),
            ]));
        }
        return $collector->getSortedEvents();
    }
}
