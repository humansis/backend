<?php

namespace CommonBundle\Utils;

use Symfony\Component\HttpFoundation\Response;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\MimeType\FileinfoMimeTypeGuesser;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class PdfService
{
    public function printPdf($html, string $name)
    {
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');
        $pdfOptions->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($pdfOptions);

        try {
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            $output = $dompdf->output();
            $pdfFilepath =  getcwd() . '/'.$name.'.pdf';
            file_put_contents($pdfFilepath, $output);

            $response = new BinaryFileResponse($pdfFilepath);
            $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $name.'.pdf');
            $response->headers->set('Content-Type', 'application/pdf');
            $response->deleteFileAfterSend(true);

            return $response;
        } catch (\Exception $e) {
            throw $e;
        }

        return new Response('');
    }
}
