<?php

declare(strict_types=1);

namespace Controller\VendorApp;

use FOS\RestBundle\Controller\Annotations as Rest;
use Component\LogsStorage\LogsStorageService;
use League\Flysystem\FilesystemException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Entity\Vendor;

class LogController extends AbstractVendorAppController
{
    public function __construct(private readonly LogsStorageService $logsStorageService)
    {
    }

    /**
     * @Rest\Post("/vendor-app/v1/vendors/{id}/logs")
     *
     *
     *
     * @throws FilesystemException
     */
    public function uploadLogs(Vendor $vendor, Request $request): Response
    {
        /** @var UploadedFile[] $files */
        $files = $request->files->all();

        foreach ($files as $file) {
            $this->logsStorageService->uploadVendorApp($vendor, $file);
        }

        return (new Response())->setStatusCode(Response::HTTP_NO_CONTENT);
    }
}
