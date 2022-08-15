<?php
declare(strict_types=1);

namespace NewApiBundle\Controller\VendorApp;

use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\Component\LogsStorage\LogsStorageService;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use NewApiBundle\Entity\Vendor;

class LogController extends AbstractVendorAppController
{
    /**
     * @var LogsStorageService
     */
    private $logsStorageService;

    public function __construct(LogsStorageService $logsStorageService)
    {
        $this->logsStorageService = $logsStorageService;
    }

    /**
     * @Rest\Post("/vendor-app/v1/vendors/{id}/logs")
     *
     * @param Vendor   $vendor
     *
     * @param Request  $request
     *
     * @return Response
     * @throws \League\Flysystem\FilesystemException
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
