<?php

declare(strict_types=1);

namespace Component\Smartcard\Invoice;

use Component\Smartcard\Invoice\Exception\WrongInvoicingStateHttpException;
use Entity\Smartcard\PreliminaryInvoice;
use Entity\Vendor;
use Enum\VendorInvoicingState;
use Repository\Smartcard\PreliminaryInvoiceRepository;

class PreliminaryInvoiceService
{
    public function __construct(private readonly PreliminaryInvoiceRepository $preliminaryInvoiceRepository, private readonly InvoiceChecker $invoiceChecker)
    {
    }

    /**
     * @return PreliminaryInvoiceDto[]
     */
    public function getArrayOfPreliminaryInvoicesDtoByVendor(Vendor $vendor): array
    {
        $preliminaryInvoices = $this->getPreliminaryInvoicesByVendor($vendor);
        $preliminaryInvoicesDto = [];
        foreach ($preliminaryInvoices as $preliminaryInvoice) {
            $canRedeem = $this->invoiceChecker->isInvoiceable($vendor, $preliminaryInvoice->getPurchaseIds());
            $preliminaryInvoicesDto[] = new PreliminaryInvoiceDto($preliminaryInvoice, $canRedeem);
        }

        return $preliminaryInvoicesDto;
    }

    /**
     * @return PreliminaryInvoice[]
     */
    public function getRedeemablePreliminaryInvoicesByVendor(Vendor $vendor): array
    {
        $redeemablePreliminaryInvoices = [];
        foreach ($this->getPreliminaryInvoicesByVendor($vendor) as $preliminaryInvoice) {
            if ($this->invoiceChecker->isInvoiceable($vendor, $preliminaryInvoice->getPurchaseIds())) {
                $redeemablePreliminaryInvoices[] = $preliminaryInvoice;
            }
        }

        return $redeemablePreliminaryInvoices;
    }

    /**
     * @param Vendor[] $vendors
     * @return Vendor[]
     * @throws WrongInvoicingStateHttpException
     */
    public function filterVendorsByInvoicing(array $vendors, string $invoicingState): array
    {
        $vendorsSelection = [];
        foreach ($vendors as $vendor) {
            /**
             * @var PreliminaryInvoice[]|null $preliminaryInvoices
             */
            $preliminaryInvoices = $this->getPreliminaryInvoicesByVendor($vendor);
            switch ($invoicingState) {
                case VendorInvoicingState::INVOICED:
                    if (count((array) $preliminaryInvoices) === 0) {
                        $vendorsSelection[] = $vendor;
                    }
                    break;
                case VendorInvoicingState::SYNC_REQUIRED:
                    if ($this->isVendorInSyncRequiredState($vendor, $preliminaryInvoices)) {
                        $vendorsSelection[] = $vendor;
                    }
                    break;
                case VendorInvoicingState::TO_REDEEM:
                    if ($this->isVendorInToRedeemState($vendor, $preliminaryInvoices)) {
                        $vendorsSelection[] = $vendor;
                    }
                    break;
                default:
                    throw new WrongInvoicingStateHttpException(
                        "$invoicingState is invalid invoicing state. Allowed states are [" . implode(
                            ',',
                            VendorInvoicingState::values()
                        ) . "]"
                    );
            }
        }

        return $vendorsSelection;
    }

    /**
     * @return PreliminaryInvoice[]
     */
    private function getPreliminaryInvoicesByVendor(Vendor $vendor): array
    {
        return $this->preliminaryInvoiceRepository->findBy(['vendor' => $vendor]);
    }

    /**
     * @param PreliminaryInvoice[] $preliminaryInvoices
     */
    private function isVendorInSyncRequiredState(Vendor $vendor, array $preliminaryInvoices): bool
    {
        if (count($preliminaryInvoices) > 0) {
            foreach ($preliminaryInvoices as $preliminaryInvoice) {
                if (!$this->invoiceChecker->isInvoiceable($vendor, $preliminaryInvoice->getPurchaseIds())) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param PreliminaryInvoice[] $preliminaryInvoices
     */
    private function isVendorInToRedeemState(Vendor $vendor, array $preliminaryInvoices): bool
    {
        if (count($preliminaryInvoices) > 0) {
            foreach ($preliminaryInvoices as $preliminaryInvoice) {
                if ($this->invoiceChecker->isInvoiceable($vendor, $preliminaryInvoice->getPurchaseIds())) {
                    return true;
                }
            }
        }

        return false;
    }
}
