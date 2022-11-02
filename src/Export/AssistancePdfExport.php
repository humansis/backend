<?php

declare(strict_types=1);

namespace Export;

use Entity\Organization;
use Utils\PdfService;
use Entity\Assistance;
use Twig\Environment;

class AssistancePdfExport
{
    public function __construct(private readonly PdfService $pdfService, private readonly Environment $twig)
    {
    }

    public function export(Assistance $assistance, Organization $organization)
    {
        $html = $this->twig->render('@Transaction/Pdf/transactions.html.twig', [
            'assistance' => $assistance,
            'organisation' => $organization,
        ]);

        return $this->pdfService->printPdf($html, 'landscape', 'distribution');
    }
}
