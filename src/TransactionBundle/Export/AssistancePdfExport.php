<?php

declare(strict_types=1);

namespace TransactionBundle\Export;

use CommonBundle\Entity\Organization;
use CommonBundle\Utils\PdfService;
use DistributionBundle\Entity\Assistance;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class AssistancePdfExport
{
    /** @var TranslatorInterface */
    private $translator;

    /** @var Environment */
    private $twig;

    /** @var PdfService */
    private $pdfService;

    public function __construct(TranslatorInterface $translator, Environment $twig, PdfService $pdfService)
    {
        $this->translator = $translator;
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
