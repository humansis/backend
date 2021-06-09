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

/**
 * @deprecated legacy raw export for SC export
 */
class SmartcardExport
{
    /** @var ExportService */
    private $csvExportService;

    /** @var EntityManagerInterface */
    private $em;


    public function __construct(ExportService $exportService, EntityManagerInterface $em)
    {
        $this->csvExportService = $exportService;
        $this->em = $em;
    }

    public function exportSpreadsheet(Assistance $assistance, string $type)
    {
        /** @var AssistanceBeneficiary[] $assistanceBeneficiaries */
        $assistanceBeneficiaries = $this->em->getRepository(AssistanceBeneficiary::class)->findByAssistance($assistance);

        $exportableTable = [];
        foreach ($assistanceBeneficiaries as $db) {
            $commonFields = $db->getBeneficiary()->getCommonExportFields();

            /** @var SmartcardDeposit|null $deposit */
            $deposit = $this->em->getRepository(SmartcardDeposit::class)->findByAssistanceBeneficiary($db);
            if ($deposit) {
                $exportableTable[] = array_merge($commonFields, [
                    'Amount Sent' => $deposit->getValue(),
                    'Sent At' => $deposit->getCreatedAt()->format('d-m-Y'),
                    'Suspect Smartcard' => $deposit->getSmartcard()->isSuspicious() ? 'Yes' : 'No',
                ]);
            } else {
                $exportableTable[] = array_merge($commonFields, [
                    'Amount Sent' => null,
                    'Sent At' => null,
                    'Suspect Smartcard' => null,
                ]);
            }
        }

        return $this->csvExportService->export($exportableTable, 'smartcards', $type);
    }

}
