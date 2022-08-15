<?php declare(strict_types=1);

namespace NewApiBundle\Component\Smartcard\Analytics;

use NewApiBundle\Entity\Beneficiary;
use NewApiBundle\Entity\Assistance;
use NewApiBundle\Entity\AssistanceBeneficiary;
use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\Entity\Assistance\ReliefPackage;
use NewApiBundle\Entity\SynchronizationBatch;
use NewApiBundle\Repository\SynchronizationBatchRepository;
use NewApiBundle\Entity\Smartcard;
use NewApiBundle\Entity\SmartcardDeposit;
use NewApiBundle\Entity\SmartcardPurchase;
use NewApiBundle\Entity\Invoice;
use NewApiBundle\Entity\Vendor;
use NewApiBundle\Repository\SmartcardPurchaseRepository;
use NewApiBundle\Repository\SmartcardInvoiceRepository;
use NewApiBundle\Repository\SmartcardRepository;

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
    /** @var SmartcardInvoiceRepository */
    private $invoiceRepository;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager
    ) {
        $this->purchaseRepository = $entityManager->getRepository(SmartcardPurchase::class);
        $this->smartcardRepository = $entityManager->getRepository(Smartcard::class);
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

            $this->collectAssistanceEvents($collector, $assistance, $assistanceBeneficiary, $assistanceBeneficiary->getReliefPackages()->toArray());

            foreach ($assistanceBeneficiary->getSmartcardDeposits() as $deposit) {
                $this->collectDepositEvents($collector, $deposit, $assistance, $deposit->getSmartcard());
            }
        }

        foreach ($this->purchaseRepository->findByBeneficiary($beneficiary) as $purchase) {
            $this->collectPurchaseEvents($collector, $purchase, true);
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

            $this->collectAssistanceEvents($collector, $assistance, $assistanceBeneficiary, [$reliefPackage]);

            $this->collectDepositEvents($collector, $deposit, $assistance, $smartcard);
        }

        foreach ($smartcard->getPurchases() as $purchase) {
            $this->collectPurchaseEvents($collector, $purchase, true);
        }
    }

    public function getVendorEvents(Vendor $vendor): array
    {
        $collector = new EventCollector();
        foreach ($this->purchaseRepository->findBy(['vendor'=>$vendor]) as $purchase) {
            $this->collectPurchaseEvents($collector, $purchase, false);
        }
        foreach ($this->invoiceRepository->findByVendor($vendor) as $invoice) {
            $collector->add(new Event('invoice', 'made', $invoice->getInvoicedAt(), [
                $invoice->getProject(),
                $invoice->getVendor(),
            ], [
                'value' => $invoice->getValue().' '.$invoice->getCurrency(),
                'accountantId' => $invoice->getInvoicedBy()->getId(),
                'accountantName' => $invoice->getInvoicedBy()->getUsername(),
            ]));
        }
        foreach ($this->depositSyncRepository->findBy(['createdBy' => $vendor]) as $sync) {
            $this->collectSynchronizationBatchEvents('deposit', $collector, $sync, $vendor);
        }
        foreach ($this->purchaseSyncRepository->findBy(['createdBy' => $vendor]) as $sync) {
            $this->collectSynchronizationBatchEvents('purchase', $collector, $sync, $vendor);
        }
        return $collector->getSortedEvents();
    }

    /**
     * @param EventCollector    $collector
     * @param SmartcardPurchase $purchase
     * @param bool              $extractInvoices
     */
    protected function collectPurchaseEvents(EventCollector $collector, SmartcardPurchase $purchase, bool $extractInvoices): void
    {
        $collector->add(new Event('purchase', 'made', $purchase->getCreatedAt(), [
            $purchase->getAssistance(),
            $purchase,
            $purchase->getSmartcard(),
            $purchase->getVendor(),
        ], [
            'value' => $purchase->getRecordsValue().' '.$purchase->getCurrency(),
        ]));
        if ($extractInvoices && $purchase->getRedemptionBatch()) {
            $collector->add(new Event('purchase', 'invoiced', $purchase->getRedeemedAt(), [
                $purchase
            ], [
            ]));
        }
    }

    /**
     * @param string         $syncType
     * @param EventCollector $collector
     * @param                $sync
     * @param Vendor         $vendor
     */
    protected function collectSynchronizationBatchEvents(string $syncType, EventCollector $collector, $sync, Vendor $vendor): void
    {
        $collector->add(new Event($syncType, 'sync uploaded', $sync->getCreatedAt(), [$vendor], [
            'syncId' => $sync->getId(),
            'source' => $sync->getSource(),
        ]));
        $collector->add(new Event($syncType, 'sync validated', $sync->getValidatedAt(), [$vendor], [
            'syncId' => $sync->getId(),
            'source' => $sync->getSource(),
        ]));
    }

    /**
     * @param EventCollector                                   $collector
     * @param ReliefPackage[]          $reliefPackages
     * @param Assistance|null       $assistance
     * @param AssistanceBeneficiary $assistanceBeneficiary
     */
    private function collectAssistanceEvents(EventCollector                                   $collector,
                                             Assistance                                      $assistance,
                                             AssistanceBeneficiary $assistanceBeneficiary,
                                             array                                   $reliefPackages
    ): void
    {
        $collector->add(new Event('assistance', 'started', $assistance->getDateDistribution(), [
            $assistance,
            $assistanceBeneficiary,
        ], []));

        if ($assistance->getDateExpiration()) {
            $collector->add(new Event('assistance', 'expired', $assistance->getDateExpiration(), [
                $assistance,
                $assistanceBeneficiary,
            ], []));
        }

        foreach ($reliefPackages as $reliefPackage) {
            $collector->add(new Event('assistance', 'set to distribute', $reliefPackage->getCreatedAt(), [
                $assistance,
                $assistanceBeneficiary,
                $reliefPackage,
            ], []));
        }
    }

    /**
     * @param EventCollector   $collector
     * @param SmartcardDeposit $deposit
     * @param Assistance       $assistance
     * @param Smartcard        $smartcard
     */
    private function collectDepositEvents(EventCollector $collector, SmartcardDeposit $deposit, Assistance $assistance, Smartcard $smartcard): void
    {
        $collector->add(new Event('deposit', 'sync', $deposit->getCreatedAt(), [$deposit], []));

        $collector->add(new Event('deposit', 'got money', $deposit->getDistributedAt(), [
            $assistance, $deposit, $smartcard,
        ], [
            'value' => $deposit->getValue(),
        ]));
    }
}
