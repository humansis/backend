<?php

declare(strict_types=1);

namespace DistributionBundle\Export;

use CommonBundle\Utils\ExportService;
use CommonBundle\Utils\PdfService;
use DistributionBundle\Entity\Assistance;
use DistributionBundle\Entity\AssistanceBeneficiary;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\TwigEngine;
use VoucherBundle\Entity\SmartcardDeposit;

class SmartcardExport
{
    /** @var ExportService */
    private $csvExportService;

    /** @var EntityManagerInterface */
    private $em;

    /** @var TwigEngine */
    private $templating;

    /** @var PdfService */
    private $pdfService;

    public function __construct(ExportService $exportService, EntityManagerInterface $em, TwigEngine $templating, PdfService $pdfService)
    {
        $this->csvExportService = $exportService;
        $this->em = $em;
        $this->templating = $templating;
        $this->pdfService = $pdfService;
    }

    public function exportSpreadsheet(Assistance $assistance, string $type)
    {
        /** @var AssistanceBeneficiary[] $assistanceBeneficiaries */
        $assistanceBeneficiaries = $this->em->getRepository(AssistanceBeneficiary::class)->findByAssistance($assistance);

        $exportableTable = [];
        foreach ($assistanceBeneficiaries as $db) {
            /** @var SmartcardDeposit|null $deposit */
            $deposit = $this->em->getRepository(SmartcardDeposit::class)->findByDistributionBeneficiary($db);
            if ($deposit) {
                $commonFields = $db->getBeneficiary()->getCommonExportFields();

                $exportableTable[] = array_merge($commonFields, [
                    'Amount Sent' => $deposit->getValue(),
                    'Sent At' => $deposit->getCreatedAt()->format('d-m-Y'),
                    'Suspect Smartcard' => $deposit->getSmartcard()->isSuspicious() ? 'Yes' : 'No',
                ]);
            }
        }

        return $this->csvExportService->export($exportableTable, 'smartcards', $type);
    }

    public function exportPdf(Assistance $assistance)
    {
        $deposits = [];

        /** @var AssistanceBeneficiary[] $assistanceBeneficiaries */
        $assistanceBeneficiaries = $this->em->getRepository(AssistanceBeneficiary::class)->findByAssistance($assistance);
        foreach ($assistanceBeneficiaries as $db) {
            /** @var SmartcardDeposit|null $deposit */
            $deposit = $this->em->getRepository(SmartcardDeposit::class)->findByDistributionBeneficiary($db);
            if ($deposit) {
                $deposits[$db->getId()] = $deposit;
            }
        }

        $data = array_merge([
                'assistance' => $assistance,
                'assistanceBeneficiaries' => $assistanceBeneficiaries,
                'deposits' => $deposits,
            ],
            $this->pdfService->getInformationStyle()
        );

        $html = $this->templating->render('@Distribution/Pdf/smartcard.html.twig', $data);

        return $this->pdfService->printPdf($html, 'portrait', 'smarcards');
    }
}
