<?php

declare(strict_types=1);

namespace Component\Smartcard\Analytics;

use Entity\Beneficiary;
use Entity\Assistance;
use Entity\AssistanceBeneficiary;
use Doctrine\ORM\EntityManagerInterface;
use Entity\Assistance\ReliefPackage;
use Entity\SynchronizationBatch;
use Entity\SmartcardBeneficiary;
use Entity\SmartcardDeposit;
use Entity\SmartcardPurchase;
use Entity\Invoice;
use Entity\Vendor;
use Repository\SmartcardInvoiceRepository;
use Repository\SmartcardPurchaseRepository;
use Repository\SmartcardBeneficiaryRepository;
use Repository\SynchronizationBatchRepository;

class EventService
{
    private readonly SmartcardPurchaseRepository $purchaseRepository;

    private readonly SmartcardBeneficiaryRepository $smartcardBeneficiaryRepository;

    private readonly SynchronizationBatchRepository $purchaseSyncRepository;

    private readonly SynchronizationBatchRepository $depositSyncRepository;

    private readonly SmartcardInvoiceRepository $invoiceRepository;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->purchaseRepository = $entityManager->getRepository(SmartcardPurchase::class);
        $this->smartcardBeneficiaryRepository = $entityManager->getRepository(SmartcardBeneficiary::class);
        $this->depositSyncRepository = $entityManager->getRepository(SynchronizationBatch\Deposits::class);
        $this->purchaseSyncRepository = $entityManager->getRepository(SynchronizationBatch\Purchases::class);
        $this->invoiceRepository = $entityManager->getRepository(Invoice::class);
    }

    public function getBeneficiaryEvents(Beneficiary $beneficiary): array
    {
        $collector = new EventCollector();
        $collector->add(new Event('beneficiary', 'updated', $beneficiary->getUpdatedOn()));
        foreach ($beneficiary->getDistributionBeneficiaries() as $assistanceBeneficiary) {
            $assistance = $assistanceBeneficiary->getAssistance();

            $this->collectAssistanceEvents(
                $collector,
                $assistance,
                $assistanceBeneficiary,
                $assistanceBeneficiary->getReliefPackages()->toArray()
            );

            foreach ($assistanceBeneficiary->getSmartcardDeposits() as $deposit) {
                $this->collectDepositEvents($collector, $deposit, $assistance, $deposit->getSmartcard());
            }
        }

        foreach ($this->purchaseRepository->findByBeneficiary($beneficiary) as $purchase) {
            $this->collectPurchaseEvents($collector, $purchase, true);
        }

        return $collector->getSortedEvents();
    }

    public function getSmartcardEvents(SmartcardBeneficiary $smartcardBeneficiary): array
    {
        $collector = new EventCollector();
        $this->collectSmartcard($collector, $smartcardBeneficiary);

        return $collector->getSortedEvents();
    }

    public function getSmartcardsEvents(string $serialNumber): array
    {
        $smartcardBeneficiaries = $this->smartcardBeneficiaryRepository->findBy(['serialNumber' => $serialNumber]);
        $collector = new EventCollector();
        foreach ($smartcardBeneficiaries as $smartcardBeneficiary) {
            $this->collectSmartcard($collector, $smartcardBeneficiary);
        }

        return $collector->getSortedEvents();
    }

    private function collectSmartcard(EventCollector $collector, SmartcardBeneficiary $smartcardBeneficiary): void
    {
        foreach ($smartcardBeneficiary->getDeposites() as $deposit) {
            $reliefPackage = $deposit->getReliefPackage();
            $assistanceBeneficiary = $reliefPackage->getAssistanceBeneficiary();
            $assistance = $assistanceBeneficiary->getAssistance();

            $this->collectAssistanceEvents($collector, $assistance, $assistanceBeneficiary, [$reliefPackage]);

            $this->collectDepositEvents($collector, $deposit, $assistance, $smartcardBeneficiary);
        }

        foreach ($smartcardBeneficiary->getPurchases() as $purchase) {
            $this->collectPurchaseEvents($collector, $purchase, true);
        }
    }

    public function getVendorEvents(Vendor $vendor): array
    {
        $collector = new EventCollector();
        foreach ($this->purchaseRepository->findBy(['vendor' => $vendor]) as $purchase) {
            $this->collectPurchaseEvents($collector, $purchase, false);
        }
        foreach ($this->invoiceRepository->findByVendor($vendor) as $invoice) {
            $collector->add(
                new Event('invoice', 'made', $invoice->getInvoicedAt(), [
                    $invoice->getProject(),
                    $invoice->getVendor(),
                ], [
                    'value' => $invoice->getValue() . ' ' . $invoice->getCurrency(),
                    'accountantId' => $invoice->getInvoicedBy()->getId(),
                    'accountantName' => $invoice->getInvoicedBy()->getUsername(),
                ])
            );
        }
        foreach ($this->depositSyncRepository->findBy(['createdBy' => $vendor]) as $sync) {
            $this->collectSynchronizationBatchEvents('deposit', $collector, $sync, $vendor);
        }
        foreach ($this->purchaseSyncRepository->findBy(['createdBy' => $vendor]) as $sync) {
            $this->collectSynchronizationBatchEvents('purchase', $collector, $sync, $vendor);
        }

        return $collector->getSortedEvents();
    }

    protected function collectPurchaseEvents(
        EventCollector $collector,
        SmartcardPurchase $purchase,
        bool $extractInvoices
    ): void {
        $collector->add(
            new Event('purchase', 'made', $purchase->getCreatedAt(), [
                $purchase->getAssistance(),
                $purchase,
                $purchase->getSmartcard(),
                $purchase->getVendor(),
            ], [
                'value' => $purchase->getRecordsValue() . ' ' . $purchase->getCurrency(),
            ])
        );
        if ($extractInvoices && $purchase->getInvoice()) {
            $collector->add(
                new Event('purchase', 'invoiced', $purchase->getInvoicedAt(), [
                    $purchase,
                ], [
                ])
            );
        }
    }

    /**
     * @param                $sync
     */
    protected function collectSynchronizationBatchEvents(
        string $syncType,
        EventCollector $collector,
        $sync,
        Vendor $vendor
    ): void {
        $collector->add(
            new Event($syncType, 'sync uploaded', $sync->getCreatedAt(), [$vendor], [
                'syncId' => $sync->getId(),
                'source' => $sync->getSource(),
            ])
        );
        $collector->add(
            new Event($syncType, 'sync validated', $sync->getValidatedAt(), [$vendor], [
                'syncId' => $sync->getId(),
                'source' => $sync->getSource(),
            ])
        );
    }

    /**
     * @param ReliefPackage[] $reliefPackages
     * @param Assistance|null $assistance
     */
    private function collectAssistanceEvents(
        EventCollector $collector,
        Assistance $assistance,
        AssistanceBeneficiary $assistanceBeneficiary,
        array $reliefPackages
    ): void {
        $collector->add(
            new Event('assistance', 'started', $assistance->getDateDistribution(), [
                $assistance,
                $assistanceBeneficiary,
            ], [])
        );

        if ($assistance->getDateExpiration()) {
            $collector->add(
                new Event('assistance', 'expired', $assistance->getDateExpiration(), [
                    $assistance,
                    $assistanceBeneficiary,
                ], [])
            );
        }

        foreach ($reliefPackages as $reliefPackage) {
            $collector->add(
                new Event('assistance', 'set to distribute', $reliefPackage->getCreatedAt(), [
                    $assistance,
                    $assistanceBeneficiary,
                    $reliefPackage,
                ], [])
            );
        }
    }

    private function collectDepositEvents(
        EventCollector $collector,
        SmartcardDeposit $deposit,
        Assistance $assistance,
        SmartcardBeneficiary $smartcardBeneficiary
    ): void {
        $collector->add(new Event('deposit', 'sync', $deposit->getCreatedAt(), [$deposit], []));

        $collector->add(
            new Event('deposit', 'got money', $deposit->getDistributedAt(), [
                $assistance,
                $deposit,
                $smartcardBeneficiary,
            ], [
                'value' => $deposit->getValue(),
            ])
        );
    }
}
