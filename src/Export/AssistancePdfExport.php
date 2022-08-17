<?php

declare(strict_types=1);

namespace Export;

use Entity\Organization;
use Utils\PdfService;
use Entity\Assistance;
use Twig\Environment;

class AssistancePdfExport
{
    /** @var Environment */
    private $twig;

    /** @var PdfService */
    private $pdfService;

    public function __construct(PdfService $pdfService, Environment $twig)
    {
        $this->twig = $twig;
        $this->pdfService = $pdfService;
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
