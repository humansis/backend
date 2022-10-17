<?php

declare(strict_types=1);

namespace Component\Smartcard\Invoice;

use Entity\Smartcard\PreliminaryInvoice;

class PreliminaryInvoiceDto
{
    /**
     * @var PreliminaryInvoice
     */
    private $preliminaryInvoice;

    /**
     * @var bool
     */
    private $canRedeem;

    public function __construct(PreliminaryInvoice $preliminaryInvoice, bool $canRedeem)
    {
        $this->preliminaryInvoice = $preliminaryInvoice;
        $this->canRedeem = $canRedeem;
    }

    /**
     * @return PreliminaryInvoice
     */
    public function getPreliminaryInvoice(): PreliminaryInvoice
    {
        return $this->preliminaryInvoice;
    }

    /**
     * @param PreliminaryInvoice $preliminaryInvoice
     */
    public function setPreliminaryInvoice(PreliminaryInvoice $preliminaryInvoice): void
    {
        $this->preliminaryInvoice = $preliminaryInvoice;
    }

    /**
     * @return bool
     */
    public function canRedeem(): bool
    {
        return $this->canRedeem;
    }

    /**
     * @param bool $canRedeem
     */
    public function setCanRedeem(bool $canRedeem): void
    {
        $this->canRedeem = $canRedeem;
    }
}
