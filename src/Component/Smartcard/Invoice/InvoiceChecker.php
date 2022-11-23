<?php

declare(strict_types=1);

namespace Component\Smartcard\Invoice;

use Component\Smartcard\Invoice\Exception\AlreadyRedeemedInvoiceException;
use Component\Smartcard\Invoice\Exception\NotRedeemableInvoiceException;
use Entity\Project;
use Entity\SmartcardPurchase;
use Entity\Vendor;
use Repository\SmartcardDepositRepository;
use Repository\SmartcardPurchaseRepository;

class InvoiceChecker
{
    public function __construct(private readonly SmartcardPurchaseRepository $smartcardPurchaseRepository, private readonly SmartcardDepositRepository $smartcardDepositRepository)
    {
    }

    public function isInvoiceable(Vendor $vendor, array $purchaseIds): bool
    {
        try {
            $this->checkIfPurchasesCanBeInvoiced($vendor, $purchaseIds);

            return true;
        } catch (AlreadyRedeemedInvoiceException | NotRedeemableInvoiceException) {
            return false;
        }
    }

    /**
     * @param int[] $purchaseIds
     * @throws AlreadyRedeemedInvoiceException
     * @throws NotRedeemableInvoiceException
     */
    public function checkIfPurchasesCanBeInvoiced(Vendor $vendor, array $purchaseIds): void
    {
        $purchases = $this->smartcardPurchaseRepository->findBy([
            'id' => $purchaseIds,
        ], ['id' => 'asc']);
        if (count($purchases) === 0) {
            throw new NotRedeemableInvoiceException(
                "There is no purchase to redeem for Vendor #{$vendor->getId()}."
            );
        }

        $currency = $purchases[0]->getCurrency();
        $assistance = $purchases[0]->getAssistance();
        $project = $assistance ? $assistance->getProject() : null;
        if (!$project) {
            throw new NotRedeemableInvoiceException("Purchase #{$purchases[0]->getId()} has no project.");
        }

        foreach ($purchases as $purchase) {
            $this->checkVendorConsistency($purchase, $vendor);
            $this->checkIfPurchaseWasNotRedeemed($purchase);
            $this->checkCurrencyConsistency($purchase, $currency);
            $this->checkProjectConsistency($purchase, $project);
            $this->checkDeposits($purchase);
        }
    }

    /**
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
     * @throws NotRedeemableInvoiceException
     */
    private function checkProjectConsistency(SmartcardPurchase $smartcardPurchase, Project $project): void
    {
        $projectFromPurchase = null;
        $assistance = $smartcardPurchase->getAssistance();
        $projectFromPurchase = $assistance?->getProject();
        if (!$projectFromPurchase) {
            throw new NotRedeemableInvoiceException("Purchase #{$smartcardPurchase->getId()} has no project.");
        }
        if ($projectFromPurchase->getId() !== $project->getId()) {
            throw new NotRedeemableInvoiceException(
                "Purchases have inconsistent projects. {$projectFromPurchase->getId()} in {$smartcardPurchase->getId()} is different than {$project->getId()}"
            );
        }
    }

    /**
     * @throws NotRedeemableInvoiceException
     */
    private function checkCurrencyConsistency(SmartcardPurchase $smartcardPurchase, string $currency): void
    {
        if ($smartcardPurchase->getCurrency() !== $currency) {
            throw new NotRedeemableInvoiceException(
                "Purchases have inconsistent currencies. {$smartcardPurchase->getCurrency()} in {$smartcardPurchase->getId()} is different than $currency"
            );
        }
    }

    /**
     * @throws AlreadyRedeemedInvoiceException
     */
    private function checkIfPurchaseWasNotRedeemed(SmartcardPurchase $smartcardPurchase): void
    {
        if ($smartcardPurchase->getRedeemedAt()) {
            throw new AlreadyRedeemedInvoiceException($smartcardPurchase);
        }
    }

    /**
     * @throws NotRedeemableInvoiceException
     */
    private function checkVendorConsistency(SmartcardPurchase $smartcardPurchase, Vendor $vendor): void
    {
        if ($smartcardPurchase->getVendor()->getId() !== $vendor->getId()) {
            throw new NotRedeemableInvoiceException(
                "Inconsistent vendor and purchase in purchase #{$smartcardPurchase->getId()}. Vendor should be {$vendor->getId()}"
            );
        }
    }
}
