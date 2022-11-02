<?php

declare(strict_types=1);

namespace Component\Smartcard\Invoice;

use Component\Smartcard\Invoice\Exception\CanNotCreateInvoiceHttpException;
use Component\Smartcard\Invoice\Exception\NotRedeemableInvoiceException;
use DateTime;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Entity\Invoice;
use Entity\SmartcardPurchase;
use Entity\User;
use Entity\Vendor;
use InputType\SmartcardInvoiceCreateInputType;
use Repository\SmartcardInvoiceRepository;
use Repository\SmartcardPurchaseRepository;

class InvoiceFactory
{
    public function __construct(private readonly SmartcardPurchaseRepository $smartcardPurchaseRepository, private readonly SmartcardInvoiceRepository $smartcardInvoiceRepository, private readonly InvoiceChecker $invoiceChecker)
    {
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function create(Vendor $vendor, SmartcardInvoiceCreateInputType $invoiceInputType, User $redeemedBy): Invoice
    {
        try {
            $this->invoiceChecker->checkIfPurchasesCanBeInvoiced($vendor, $invoiceInputType->getPurchaseIds());
        } catch (NotRedeemableInvoiceException $e) {
            throw new CanNotCreateInvoiceHttpException($e->getMessage());
        }

        /**
         * @var SmartcardPurchase[] $purchases
         */
        $purchases = $this->smartcardPurchaseRepository->findBy([
            'id' => $invoiceInputType->getPurchaseIds(),
        ], ['id' => 'asc']);


        $invoice = new Invoice(
            $vendor,
            $purchases[0]->getAssistance()->getProject(),
            new DateTime(),
            $redeemedBy,
            $this->smartcardPurchaseRepository->countPurchasesValue($purchases),
            $purchases[0]->getCurrency(),
            $vendor->getContractNo(),
            $vendor->getVendorNo(),
            $purchases
        );

        foreach ($purchases as $purchase) {
            $purchase->setRedemptionBatch($invoice);
        }
        $this->smartcardInvoiceRepository->save($invoice);

        return $invoice;
    }
}
