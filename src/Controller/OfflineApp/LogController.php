<?php
declare(strict_types=1);

namespace Controller\OfflineApp;

use Component\LogsStorage\LogsStorageService;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Entity\User;
use FOS\RestBundle\Controller\Annotations as Rest;

class LogController extends AbstractOfflineAppController
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
     * @Rest\Post("/offline-app/v1/users/{id}/logs")
     *
     * @param User    $user
     * @param Request $request
     *
     * @return JsonResponse
     * @throws \League\Flysystem\FilesystemException
     */
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
