<?php

declare(strict_types=1);

namespace Component\Smartcard\Invoice;

use Component\Smartcard\Invoice\Exception\AlreadyRedeemedPurchaseException;
use Component\Smartcard\Invoice\Exception\SmartcardPurchaseException;
use DateTime;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Entity\Invoice;
use Entity\Project;
use Entity\SmartcardPurchase;
use Entity\User;
use Entity\Vendor;
use InputType\SmartcardInvoice;
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
     * @param SmartcardInvoice $invoiceInputType
     * @param User $redeemedBy
     * @return Invoice
     * @throws AlreadyRedeemedPurchaseException
     * @throws SmartcardPurchaseException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function create(Vendor $vendor, SmartcardInvoice $invoiceInputType, User $redeemedBy): Invoice
    {
        $this->vendor = $vendor;
        $this->purchases = $this->getPurchases($invoiceInputType->getPurchases());
        if (count($this->purchases) === 0) {
            throw new SmartcardPurchaseException('There is no purchase to redeem.');
        }
        $this->currency = $this->purchases[0]->getCurrency();
        $assistance = $this->purchases[0]->getAssistance();
        $this->project = $assistance ? $assistance->getProject() : null;

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
     * @return void
     * @throws AlreadyRedeemedPurchaseException
     * @throws SmartcardPurchaseException
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
     * @throws SmartcardPurchaseException
     */
    private function checkDeposits(SmartcardPurchase $smartcardPurchase): void
    {
        $deposits = $this->smartcardDepositRepository->getDepositsByBeneficiaryAndAssistance(
            $smartcardPurchase->getSmartcard()->getBeneficiary(),
            $smartcardPurchase->getAssistance()
        );
        if (count($deposits) === 0) {
            throw new SmartcardPurchaseException(
                "There is no connected deposit with purchase #{$smartcardPurchase->getId()}"
            );
        }
    }

    /**
     * @param SmartcardPurchase $smartcardPurchase
     * @return void
     * @throws SmartcardPurchaseException
     */
    private function checkProjectConsistency(SmartcardPurchase $smartcardPurchase): void
    {
        $project = null;
        $assistance = $smartcardPurchase->getAssistance();
        if ($assistance) {
            $project = $assistance->getProject();
        }
        if (!$project) {
            throw new SmartcardPurchaseException("Purchase #{$smartcardPurchase->getId()} has no project.");
        }
        if ($project->getId() !== $this->project->getId()) {
            throw new SmartcardPurchaseException(
                "Purchases have inconsistent projects. {$project->getId()} in {$smartcardPurchase->getId()} is different than {$this->project->getId()}"
            );
        }
    }

    /**
     * @param SmartcardPurchase $smartcardPurchase
     * @return void
     * @throws SmartcardPurchaseException
     */
    private function checkCurrencyConsistency(SmartcardPurchase $smartcardPurchase): void
    {
        if ($smartcardPurchase->getCurrency() !== $this->currency) {
            throw new SmartcardPurchaseException(
                "Purchases have inconsistent currencies. {$smartcardPurchase->getCurrency()} in {$smartcardPurchase->getId()} is different than $this->currency"
            );
        }
    }

    /**
     * @param SmartcardPurchase $smartcardPurchase
     * @return void
     * @throws AlreadyRedeemedPurchaseException
     */
    private function checkIfPurchaseWasNotRedeemed(SmartcardPurchase $smartcardPurchase): void
    {
        if ($smartcardPurchase->getRedeemedAt()) {
            throw new AlreadyRedeemedPurchaseException($smartcardPurchase);
        }
    }

    /**
     * @param SmartcardPurchase $smartcardPurchase
     * @return void
     * @throws SmartcardPurchaseException
     */
    private function checkVendorConsistency(SmartcardPurchase $smartcardPurchase): void
    {
        if ($smartcardPurchase->getVendor()->getId() !== $this->vendor->getId()) {
            throw new SmartcardPurchaseException(
                "Inconsistent vendor and purchase in purchase #{$smartcardPurchase->getId()}. Vendor should be {$this->vendor->getId()}"
            );
        }
    }

    /**
     * @param int[] $purchaseIds
     * @return SmartcardPurchase[]
     */
    private function getPurchases(array $purchaseIds): array
    {
        return $this->smartcardPurchaseRepository->findBy([
            'id' => $purchaseIds,
        ], ['id' => 'asc']);
    }
}
