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
    /**
     * @var SmartcardPurchaseRepository
     */
    private $smartcardPurchaseRepository;

    /**
     * @var SmartcardDepositRepository
     */
    private $smartcardDepositRepository;

    public function __construct(
        SmartcardPurchaseRepository $smartcardPurchaseRepository,
        SmartcardDepositRepository $smartcardDepositRepository
    ) {
        $this->smartcardPurchaseRepository = $smartcardPurchaseRepository;
        $this->smartcardDepositRepository = $smartcardDepositRepository;
    }

    /**
     * @param Vendor $vendor
     * @param int[] $purchaseIds
     * @return void
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
     * @param Project $project
     * @return void
     * @throws NotRedeemableInvoiceException
     */
    private function checkProjectConsistency(SmartcardPurchase $smartcardPurchase, Project $project): void
    {
        $projectFromPurchase = null;
        $assistance = $smartcardPurchase->getAssistance();
        if ($assistance) {
            $projectFromPurchase = $assistance->getProject();
        }
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
     * @param SmartcardPurchase $smartcardPurchase
     * @param string $currency
     * @return void
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
     * @param Vendor $vendor
     * @return void
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
