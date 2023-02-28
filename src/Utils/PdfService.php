<?php

namespace Utils;

use Exception;
use Symfony\Component\HttpFoundation\Response;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Entity\Organization;
use Doctrine\ORM\EntityManagerInterface;

class PdfService
{
    /**
     * UserService constructor.
     */
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function printPdf($html, string $orientation, string $name)
    {
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');
        $pdfOptions->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($pdfOptions);

        try {
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', $orientation);
            $dompdf->render();
            $output = $dompdf->output();
            $pdfFilepath = getcwd() . '/' . $name . '.pdf';
            file_put_contents($pdfFilepath, $output);

            $response = new BinaryFileResponse($pdfFilepath);
            $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $name . '.pdf');
            $response->headers->set('Content-Type', 'application/pdf');
            $response->deleteFileAfterSend(true);

            return $response;
        } catch (Exception $e) {
            throw $e;
        }

        return new Response('');
    }

    public function getInformationStyle()
    {
        $organization = $this->em->getRepository(Organization::class)->findOneBy([]);

        return [
            'organizationName' => $organization->getName(),
            'organizationLogo' => $organization->getLogo(),
            'footer' => $organization->getFooterContent(),
            'primaryColor' => $organization->getPrimaryColor(),
            'secondaryColor' => $organization->getSecondaryColor(),
            'font' => $organization->getFont(),
        ];
    }
}
