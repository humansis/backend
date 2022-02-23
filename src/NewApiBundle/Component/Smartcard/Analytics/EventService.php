<?php declare(strict_types=1);

namespace NewApiBundle\Component\Smartcard\Analytics;

use BeneficiaryBundle\Entity\Beneficiary;
use DistributionBundle\Entity\Assistance;
use DistributionBundle\Entity\AssistanceBeneficiary;
use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\Entity\ReliefPackage;
use NewApiBundle\Entity\SynchronizationBatch;
use NewApiBundle\Repository\SynchronizationBatchRepository;
use VoucherBundle\Entity\Smartcard;
use VoucherBundle\Entity\SmartcardDeposit;
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

            $this->collectAssistanceEvents($collector, $assistance, $assistanceBeneficiary, $assistanceBeneficiary->getReliefPackages());

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
        if ($extractInvoices && $purchase->getRedemptionBatch()) {
            $collector->add(new Event('purchase', 'invoiced', $purchase->getRedeemedAt(), [], [
                'purchaseId' => $purchase->getId(),
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
        $collector->add(new Event($syncType, 'sync uploaded', $sync->getCreatedAt(), [], [
            'syncId' => $sync->getId(),
            'source' => $sync->getSource(),
            'vendorId' => $vendor->getId(),
            'vendorName' => $vendor->getName(),
        ]));
        $collector->add(new Event($syncType, 'sync validated', $sync->getValidatedAt(), [], [
            'syncId' => $sync->getId(),
            'source' => $sync->getSource(),
            'vendorId' => $vendor->getId(),
            'vendorName' => $vendor->getName(),
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

        foreach ($reliefPackages as $reliefPackage) {
            $collector->add(new Event('assistance', 'set to distribute', $reliefPackage->getCreatedAt(), [], [
                'assistanceId' => $assistance->getId(),
                'assistanceName' => $assistance->getName(),
                'reliefPackageId' => $reliefPackage->getId(),
                'assistanceBeneficiaryId' => $assistanceBeneficiary->getId(),
            ]));
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
}
