<?php

declare(strict_types=1);

namespace Utils\Response;

use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Mime\FileinfoMimeTypeGuesser;
use Symfony\Component\HttpFoundation\BinaryFileResponse as SymfonyBinaryFileResponse;

trait BinaryFileResponse
{
    public function createBinaryFileResponse($filename): SymfonyBinaryFileResponse
    {
        $response = new SymfonyBinaryFileResponse(getcwd() . '/' . $filename);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);
        $response->deleteFileAfterSend(true);

        $mimeTypeGuesser = new FileinfoMimeTypeGuesser();
        if ($mimeTypeGuesser->isGuesserSupported()) {
            $response->headers->set('Content-Type', $mimeTypeGuesser->guessMimeType(getcwd() . '/' . $filename));
        }

        return $response;
    }
}
