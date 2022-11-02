<?php

declare(strict_types=1);

namespace Component\Smartcard\Invoice;

use Entity\Smartcard\PreliminaryInvoice;

class PreliminaryInvoiceDto
{
    public function __construct(private PreliminaryInvoice $preliminaryInvoice, private bool $canRedeem)
    {
    }

    public function getPreliminaryInvoice(): PreliminaryInvoice
    {
        return $this->preliminaryInvoice;
    }

    public function setPreliminaryInvoice(PreliminaryInvoice $preliminaryInvoice): void
    {
        $this->preliminaryInvoice = $preliminaryInvoice;
    }

    public function canRedeem(): bool
    {
        return $this->canRedeem;
    }

    public function setCanRedeem(bool $canRedeem): void
    {
        $this->canRedeem = $canRedeem;
    }
}
