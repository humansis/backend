<?php

declare(strict_types=1);

namespace Controller\OfflineApp;

use Component\LogsStorage\LogsStorageService;
use League\Flysystem\FilesystemException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Entity\User;
use FOS\RestBundle\Controller\Annotations as Rest;

class LogController extends AbstractOfflineAppController
{
    public function __construct(private readonly LogsStorageService $logsStorageService)
    {
    }

    /**
     * @return JsonResponse
     * @throws FilesystemException
     */
    #[Rest\Post('/offline-app/v1/users/{id}/logs')]
    public function uploadLogs(User $user, Request $request): Response
    {
        /** @var UploadedFile[] $files */
        $files = $request->files->all();

        foreach ($files as $file) {
            $this->logsStorageService->uploadFieldApp($user, $file);
        }

        return (new Response())->setStatusCode(Response::HTTP_NO_CONTENT);
    }
}
