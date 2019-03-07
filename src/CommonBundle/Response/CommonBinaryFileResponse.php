<?php

declare(strict_types=1);

namespace CommonBundle\Response;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\MimeType\FileinfoMimeTypeGuesser;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class CommonBinaryFileResponse extends BinaryFileResponse
{
    public function __construct(string $filename, $directory = '', $status = 200, array $headers = array(), $public = true, $contentDisposition = null, $autoEtag = false, $autoLastModified = true)
    {
        $path = $directory . $filename;
        parent::__construct($path, $status, $headers, $public, $contentDisposition, $autoEtag, $autoLastModified);

        $this->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);
        $this->deleteFileAfterSend(true);

        $mimeTypeGuesser = new FileinfoMimeTypeGuesser();
        if ($mimeTypeGuesser->isSupported()) {
            $this->headers->set('Content-Type', $mimeTypeGuesser->guess($path));
        } else {
            $this->headers->set('Content-Type', 'text/plain');
        }
    }
}
