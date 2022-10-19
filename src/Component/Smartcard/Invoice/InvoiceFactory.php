<?php

declare(strict_types=1);

namespace Component\Smartcard\Invoice;

use Component\Smartcard\Invoice\Exception\AlreadyRedeemedInvoiceException;
use Component\Smartcard\Invoice\Exception\NotRedeemableInvoiceException;
use DateTime;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Entity\Invoice;
use Entity\Project;
use Entity\Smartcard\PreliminaryInvoice;
use Entity\SmartcardPurchase;
use Entity\User;
use Entity\Vendor;
use InputType\SmartcardInvoiceCreateInputType;
use Repository\SmartcardDepositRepository;
use Repository\SmartcardInvoiceRepository;
use Repository\SmartcardPurchaseRepository;

class InvoiceFactory
{
    /**
     * @var string|null
     */
    private $currency = null;

    /**
     * @var Project|null
     */
    private $project = null;

    /**
     * @var SmartcardPurchase[]
     */
    private $purchases = [];

    /**
     * @var Vendor
     */
    private $vendor;

    /**
     * @var SmartcardPurchaseRepository
     */
    private $smartcardPurchaseRepository;

    /**
     * @var SmartcardDepositRepository
     */
    private $smartcardDepositRepository;

    /**
     * @var SmartcardInvoiceRepository
     */
    private $smartcardInvoiceRepository;

    public function __construct(
        SmartcardPurchaseRepository $smartcardPurchaseRepository,
        SmartcardDepositRepository $smartcardDepositRepository,
        SmartcardInvoiceRepository $smartcardInvoiceRepository
    ) {
        $this->smartcardPurchaseRepository = $smartcardPurchaseRepository;
        $this->smartcardDepositRepository = $smartcardDepositRepository;
        $this->smartcardInvoiceRepository = $smartcardInvoiceRepository;
    }

    /**
     * @param Vendor $vendor
     * @param SmartcardInvoiceCreateInputType $invoiceInputType
     * @param User $redeemedBy
     * @return Invoice
     * @throws AlreadyRedeemedInvoiceException
     * @throws NotRedeemableInvoiceException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function create(Vendor $vendor, SmartcardInvoiceCreateInputType $invoiceInputType, User $redeemedBy): Invoice
    {
        $this->initialize($vendor, $invoiceInputType->getPurchaseIds());
        $this->checkPurchases();

        $invoice = new Invoice(
            $vendor,
            $this->project,
            new DateTime(),
            $redeemedBy,
            $this->smartcardPurchaseRepository->countPurchasesValue($this->purchases),
            $this->currency,
            $vendor->getContractNo(),
            $vendor->getVendorNo(),
            $this->purchases
        );

        foreach ($this->purchases as $purchase) {
            $purchase->setRedemptionBatch($invoice);
        }
        $this->smartcardInvoiceRepository->save($invoice);

        return $invoice;
    }

    /**
     * @param Vendor $vendor
     * @param int[] $purchaseIds
     * @return bool
     * @throws AlreadyRedeemedInvoiceException
     * @throws NotRedeemableInvoiceException
     */
    public function checkIfPurchasesCanBeInvoiced(Vendor $vendor, array $purchaseIds): bool
    {
        $this->initialize($vendor, $purchaseIds);
        $this->checkPurchases();

        return true;
    }

    /**
     * @param Vendor $vendor
     * @param int[] $purchaseIds
     * @return void
     * @throws NotRedeemableInvoiceException
     */
    private function initialize(Vendor $vendor, array $purchaseIds): void
    {
        $this->purchases = $this->loadPurchases($purchaseIds);
        if (count($this->purchases) === 0) {
            throw new NotRedeemableInvoiceException('There is no purchase to redeem.');
        }

        $this->vendor = $vendor;
        $this->currency = $this->purchases [0]->getCurrency();
        $assistance = $this->purchases[0]->getAssistance();
        $this->project = $assistance ? $assistance->getProject() : null;
        if (!$this->project) {
            throw new NotRedeemableInvoiceException("Purchase #{$this->purchases[0]->getId()} has no project.");
        }
    }

    /**
     * @return void
     * @throws AlreadyRedeemedInvoiceException
     * @throws NotRedeemableInvoiceException
     */
    private function checkPurchases()
    {
        foreach ($this->purchases as $purchase) {
            $this->checkVendorConsistency($purchase);
            $this->checkIfPurchaseWasNotRedeemed($purchase);
            $this->checkCurrencyConsistency($purchase);
            $this->checkProjectConsistency($purchase);
            $this->checkDeposits($purchase);
        }
    }

    /**
     * @param SmartcardPurchase $smartcardPurchase
     * @return void
     * @throws NotRedeemableInvoiceException
     */
    private function checkDeposits(SmartcardPurchase $smartcardPurchase): void
    {
        $deposits = $this->smartcardDepositRepository->getDepositsByBeneficiaryAndAssistance(
            $smartcardPurchase->getSmartcard()->getBeneficiary(),
            $smartcardPurchase->getAssistance()
        );
        if (count($deposits) === 0) {
            throw new NotRedeemableInvoiceException(
                "There is no connected deposit with purchase #{$smartcardPurchase->getId()}"
            );
        }
    }

    /**
     * @param SmartcardPurchase $smartcardPurchase
     * @return void
     * @throws NotRedeemableInvoiceException
     */
    private function checkProjectConsistency(SmartcardPurchase $smartcardPurchase): void
    {
        $project = null;
        $assistance = $smartcardPurchase->getAssistance();
        if ($assistance) {
            $project = $assistance->getProject();
        }
        if (!$project) {
            throw new NotRedeemableInvoiceException("Purchase #{$smartcardPurchase->getId()} has no project.");
        }
        if ($project->getId() !== $this->project->getId()) {
            throw new NotRedeemableInvoiceException(
                "Purchases have inconsistent projects. {$project->getId()} in {$smartcardPurchase->getId()} is different than {$this->project->getId()}"
            );
        }
    }

    /**
     * @param SmartcardPurchase $smartcardPurchase
     * @return void
     * @throws NotRedeemableInvoiceException
     */
    private function checkCurrencyConsistency(SmartcardPurchase $smartcardPurchase): void
    {
        if ($smartcardPurchase->getCurrency() !== $this->currency) {
            throw new NotRedeemableInvoiceException(
                "Purchases have inconsistent currencies. {$smartcardPurchase->getCurrency()} in {$smartcardPurchase->getId()} is different than $this->currency"
            );
        }
    }

    /**
     * @param SmartcardPurchase $smartcardPurchase
     * @return void
     * @throws AlreadyRedeemedInvoiceException
     */
    private function checkIfPurchaseWasNotRedeemed(SmartcardPurchase $smartcardPurchase): void
    {
        if ($smartcardPurchase->getRedeemedAt()) {
            throw new AlreadyRedeemedInvoiceException($smartcardPurchase);
        }
    }

    /**
     * @param SmartcardPurchase $smartcardPurchase
     * @return void
     * @throws NotRedeemableInvoiceException
     */
    private function checkVendorConsistency(SmartcardPurchase $smartcardPurchase): void
    {
        if ($smartcardPurchase->getVendor()->getId() !== $this->vendor->getId()) {
            throw new NotRedeemableInvoiceException(
                "Inconsistent vendor and purchase in purchase #{$smartcardPurchase->getId()}. Vendor should be {$this->vendor->getId()}"
            );
        }
    }

    /**
     * @param int[] $purchaseIds
     * @return SmartcardPurchase[]
     */
    private function loadPurchases(array $purchaseIds): array
    {
        return $this->smartcardPurchaseRepository->findBy([
            'id' => $purchaseIds,
        ], ['id' => 'asc']);
    }
}
