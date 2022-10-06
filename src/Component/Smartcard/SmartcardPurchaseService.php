<?php

declare(strict_types=1);

namespace Component\Smartcard;

use Repository\Smartcard\PreliminaryInvoiceRepository;
use Entity\Project;
use Entity\Vendor;
use Repository\SmartcardPurchaseRepository;

class SmartcardPurchaseService
{
    /** @var SmartcardPurchaseRepository */
    private $smartcardPurchaseRepository;

    /** @var PreliminaryInvoiceRepository */
    private $preliminaryInvoiceRepository;

    /**
     * @param SmartcardPurchaseRepository $smartcardPurchaseRepository
     * @param PreliminaryInvoiceRepository $preliminaryInvoiceRepository
     */
    public function __construct(SmartcardPurchaseRepository $smartcardPurchaseRepository, PreliminaryInvoiceRepository $preliminaryInvoiceRepository)
    {
        $this->smartcardPurchaseRepository = $smartcardPurchaseRepository;
        $this->preliminaryInvoiceRepository = $preliminaryInvoiceRepository;
    }

    public function getBy(Vendor $vendor, Project $project, string $currency)
    {
        $preliminaryInvoices = $this->preliminaryInvoiceRepository->findBy([
            'vendor' => $vendor,
            'project' => $project,
            'currency' => $currency,
        ]);
        foreach ($preliminaryInvoices as $preliminaryInvoice) {
            return $this->smartcardPurchaseRepository->findBy(['id' => $preliminaryInvoice->getPurchaseIds()]);
        }

        return [];
    }
}
