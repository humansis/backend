<?php

declare(strict_types=1);

namespace TransactionBundle\Export;

use CommonBundle\Entity\Organization;
use DistributionBundle\Entity\Assistance;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Symfony\Component\Translation\TranslatorInterface;

class TransactionPdfExport
{
    /** @var TranslatorInterface */
    private $translator;

    /** @var TwigEngine */
    private $twig;

    public function __construct(TranslatorInterface $translator, TwigEngine $twig)
    {
        $this->translator = $translator;
        $this->twig = $twig;
    }

    public function export(Assistance $assistance, Organization $organization)
    {
        $html = $this->twig->render('@Transaction/Pdf/transactions.html.twig', [
            'assistance' => $assistance,
            'organisation' => $organization,
        ]);

        echo $html;die;
    }
}
