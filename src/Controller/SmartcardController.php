<?php
declare(strict_types=1);

namespace Controller;

use Entity\Organization;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\MimeType\FileinfoMimeTypeGuesser;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Entity\Invoice;
use FOS\RestBundle\Controller\Annotations as Rest;

class SmartcardController extends AbstractController
{
    /**
     * @Rest\Get("/web-app/v1/smartcards/batch/{id}/legacy-export")
     *
     * @param Invoice $invoice
     *
     * @return Response
     *
     * @throws
     */
    public function exportLegacy(Invoice $invoice): Response
    {
        // todo find organisation by relation to smartcard
        $organization = $this->getDoctrine()->getRepository(Organization::class)->findOneBy([]);

        $filename = $this->get('distribution.export_legacy.smartcard_invoice')->export($invoice, $organization, $this->getUser());

        $response = new BinaryFileResponse(getcwd().'/'.$filename);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);
        $response->deleteFileAfterSend(true);

        $mimeTypeGuesser = new FileinfoMimeTypeGuesser();
        if ($mimeTypeGuesser->isSupported()) {
            $response->headers->set('Content-Type', $mimeTypeGuesser->guess(getcwd().'/'.$filename));
        }

        return $response;
    }
}
