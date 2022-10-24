<?php

declare(strict_types=1);

namespace Component\Smartcard\Invoice;

use Component\Smartcard\Invoice\Exception\NotRedeemableInvoiceException;
use Component\Smartcard\Invoice\Exception\WrongInvoicingStateHttpException;
use Entity\Smartcard\PreliminaryInvoice;
use Entity\Vendor;
use Enum\VendorInvoicingState;
use Repository\Smartcard\PreliminaryInvoiceRepository;

class PreliminaryInvoiceService
{
    /**
     * @var PreliminaryInvoiceRepository
     */
    private $preliminaryInvoiceRepository;

    /**
     * @var InvoiceChecker
     */
    private $invoiceChecker;

    public function __construct(
        PreliminaryInvoiceRepository $preliminaryInvoiceRepository,
        InvoiceChecker $invoiceChecker
    ) {
        $this->preliminaryInvoiceRepository = $preliminaryInvoiceRepository;
        $this->invoiceChecker = $invoiceChecker;
    }

    /**
     * @param Vendor $vendor
     * @return PreliminaryInvoiceDto[]
     */
    public function getArrayOfPreliminaryInvoicesDtoByVendor(Vendor $vendor): array
    {
        $preliminaryInvoices = $this->getPreliminaryInvoicesByVendor($vendor);
        $preliminaryInvoicesDto = [];
        foreach ($preliminaryInvoices as $preliminaryInvoice) {
            try {
                $this->invoiceChecker->checkIfPurchasesCanBeInvoiced(
                    $vendor,
                    $preliminaryInvoice->getPurchaseIds()
                );
                $canRedeem = true;
            } catch (NotRedeemableInvoiceException $e) {
                $canRedeem = false;
            }
            $preliminaryInvoicesDto[] = new PreliminaryInvoiceDto($preliminaryInvoice, $canRedeem);
        }

        return $preliminaryInvoicesDto;
    }

    /**
     * @param Vendor $vendor
     * @return PreliminaryInvoice[]
     */
    public function getRedeemablePreliminaryInvoicesByVendor(Vendor $vendor): array
    {
        $redeemablePreliminaryInvoices = [];
        foreach ($this->getPreliminaryInvoicesByVendor($vendor) as $preliminaryInvoice) {
            try {
                $this->invoiceChecker->checkIfPurchasesCanBeInvoiced($vendor, $preliminaryInvoice->getPurchaseIds());
                $redeemablePreliminaryInvoices[] = $preliminaryInvoice;
            } catch (NotRedeemableInvoiceException $e) {
                // this preliminary invoice is not redeemable
            }
        }

        return $redeemablePreliminaryInvoices;
    }

    /**
     * @param Vendor[] $vendors
     * @param string $invoicingState
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
                    if (count($preliminaryInvoices) === 0) {
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
     * @param Vendor $vendor
     * @return PreliminaryInvoice[]
     */
    private function getPreliminaryInvoicesByVendor(Vendor $vendor): array
    {
        return $this->preliminaryInvoiceRepository->findBy(['vendor' => $vendor]);
    }

    /**
     * @param Vendor $vendor
     * @param PreliminaryInvoice[] $preliminaryInvoices
     * @return bool
     */
    private function isVendorInSyncRequiredState(Vendor $vendor, array $preliminaryInvoices): bool
    {
        if (count($preliminaryInvoices) > 0) {
            foreach ($preliminaryInvoices as $preliminaryInvoice) {
                try {
                    $this->invoiceChecker->checkIfPurchasesCanBeInvoiced(
                        $vendor,
                        $preliminaryInvoice->getPurchaseIds()
                    );
                } catch (NotRedeemableInvoiceException $e) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param Vendor $vendor
     * @param PreliminaryInvoice[] $preliminaryInvoices
     * @return bool
     */
    private function isVendorInToRedeemState(Vendor $vendor, array $preliminaryInvoices): bool
    {
        if (count($preliminaryInvoices) > 0) {
            foreach ($preliminaryInvoices as $preliminaryInvoice) {
                try {
                    $this->invoiceChecker->checkIfPurchasesCanBeInvoiced(
                        $vendor,
                        $preliminaryInvoice->getPurchaseIds()
                    );

                    return true;
                } catch (NotRedeemableInvoiceException $e) {
                    // vendor is not in to redeem state for this preliminary invoice
                }
            }
        }

        return false;
    }
}
