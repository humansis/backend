<?php

declare(strict_types=1);

namespace Controller;

use Doctrine\DBAL\ConnectionException;
use Doctrine\Persistence\ManagerRegistry;
use InvalidArgumentException;
use Pagination\Paginator;
use FOS\RestBundle\Controller\Annotations as Rest;
use Component\Import\ImportService;
use Component\Import\Integrity\ImportLineFactory;
use Component\Import\UploadImportService;
use Entity;
use Enum\ImportQueueState;
use Enum\ImportState;
use InputType\Import;
use PhpOffice\PhpSpreadsheet\Reader\Exception;
use Repository\ImportQueueRepository;
use Repository\ImportRepository;
use Request\Pagination;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Mime\FileinfoMimeTypeGuesser;
use Entity\User;
use Utils\ExportTableServiceInterface;
use Utils\HouseholdExportCSVService;

class ImportController extends AbstractController
{
    final public const DISABLE_CRON = 'disable-cron-fast-forward';

    public function __construct(private readonly ImportService $importService, private readonly UploadImportService $uploadImportService, private readonly string $importInvalidFilesDirectory, private readonly int $maxFileSizeToLoad, private readonly ImportRepository $importRepo, private readonly ImportQueueRepository $importQueueRepo, private readonly ManagerRegistry $managerRegistry, private readonly HouseholdExportCSVService $householdExportCSVService, private readonly ExportTableServiceInterface $exportTableService)
    {
    }

    /**
     * @Rest\Get("/web-app/v1/imports/template")
     *
     *
     * @return JsonResponse
     */
    public function template(Request $request): Response
    {
        $countryIso3 = $request->headers->get('country');
        $type = $request->query->get('type');
        $exportableTable = $this->householdExportCSVService->getHeaders($countryIso3);
        return $this->exportTableService->export($exportableTable, 'pattern_household_' . $countryIso3, $type, true);
    }

    /**
     * @Rest\Get("/web-app/v1/imports/{id}")
     *
     *
     */
    public function item(Entity\Import $institution): JsonResponse
    {
        return $this->json($institution);
    }

    /**
     * @Rest\Get("/web-app/v1/imports")
     *
     *
     */
    public function list(
        Pagination $pagination,
        Import\FilterInputType $filterInputType,
        Import\OrderInputType $orderInputType,
        Request $request
    ): JsonResponse {
        $data = $this->importRepo->findByParams(
            $request->headers->get('country'),
            $pagination,
            $filterInputType,
            $orderInputType
        );

        return $this->json($data);
    }

    /**
     * @Rest\Post("/web-app/v1/imports")
     *
     *
     */
    public function create(Request $request, Import\CreateInputType $inputType): JsonResponse
    {
        if (!$request->headers->has('country')) {
            throw $this->createNotFoundException('Missing header attribute country');
        }

        /** @var User $user */
        $user = $this->getUser();

        $institution = $this->importService->create($request->headers->get('country'), $inputType, $user);

        return $this->json($institution);
    }

    /**
     * @Rest\Patch("/web-app/v1/imports/{id}")
     *
     *
     */
    public function updateStatus(
        Request $request,
        Entity\Import $import,
        Import\PatchInputType $inputType
    ): JsonResponse {
        $this->importService->patch($import, $inputType);

        return $this->json(null, Response::HTTP_ACCEPTED);
    }

    /**
     * @Rest\Get("/web-app/v1/imports/{id}/files")
     *
     *
     */
    public function listFiles(Entity\Import $import): JsonResponse
    {
        $data = $this->managerRegistry->getRepository(Entity\ImportFile::class)
            ->findBy([
                'import' => $import,
            ], [
                'createdAt' => 'DESC',
            ]);

        return $this->json(new Paginator($data));
    }

    /**
     * @Rest\Post("/web-app/v1/imports/{id}/files")
     *
     *
     *BinaryFileResponse
     * @throws ConnectionException
     * @throws Exception
     */
    public function uploadFile(Entity\Import $import, Request $request): JsonResponse
    {
        if (
            !in_array($import->getState(), [
            ImportState::NEW,
            ImportState::INTEGRITY_CHECKING,
            ImportState::INTEGRITY_CHECK_CORRECT,
            ImportState::INTEGRITY_CHECK_FAILED,
            ])
        ) {
            throw new InvalidArgumentException('You cannot upload file to this import.');
        }

        /** @var UploadedFile[] $files */
        $files = $request->files->all();

        if (empty($files)) {
            throw new InvalidArgumentException('Missing upload file.');
        }

        if (count($files) > 1) {
            throw new InvalidArgumentException('It is possible to upload just one file.');
        }

        $this->checkImportFileSizes($files);

        /** @var User $user */
        $user = $this->getUser();

        $importFiles = [];

        $this->importService->updateStatus($import, ImportState::UPLOADING);
        foreach ($files as $file) {
            $importFiles[] = $this->uploadImportService->uploadFile($import, $file, $user);
        }

        return $this->json(new Paginator($importFiles));
    }

    /**
     * @param UploadedFile[] $files
     *
     * @throws ConnectionException
     * @throws Exception
     */
    private function checkImportFileSizes(array $files)
    {
        foreach ($files as $file) {
            $fileSize = $file->getSize();
            $fileMaxSize = $this->maxFileSizeToLoad * 1024 * 1024;
            if ($fileSize > $fileMaxSize) {
                $mbMaxFileSize = round($fileMaxSize / (1024 * 1024), 2);
                $mbFileSize = round($fileSize / (1024 * 1024), 2);
                throw new BadRequestHttpException(
                    "File reached maximum file size! Maximum file size is {$mbMaxFileSize} MB but your file size is {$mbFileSize} MB"
                );
            }
        }
    }

    /**
     * @Rest\Delete("/web-app/v1/imports/files/{id}")
     *
     *
     */
    public function deleteFile(Entity\ImportFile $importFile): JsonResponse
    {
        if (
            !in_array($importFile->getImport()->getState(), [
            ImportState::INTEGRITY_CHECKING,
            ImportState::INTEGRITY_CHECK_CORRECT,
            ImportState::INTEGRITY_CHECK_FAILED,
            ])
        ) {
            throw new InvalidArgumentException('You cannot delete file from this import.');
        }

        $this->importService->removeFile($importFile);

        return $this->json(null, Response::HTTP_ACCEPTED);
    }

    /**
     * @Rest\Get("/web-app/v1/imports/{id}/duplicities")
     *
     *
     */
    public function duplicities(Entity\Import $import): JsonResponse
    {
        /** @var Entity\ImportHouseholdDuplicity[] $duplicities */
        $duplicities = $this->managerRegistry->getRepository(Entity\ImportHouseholdDuplicity::class)
            ->findByImport($import);

        return $this->json($duplicities);
    }

    /**
     * @Rest\Get("/web-app/v1/imports/{id}/statistics")
     *
     *
     */
    public function queueProgress(Entity\Import $import): JsonResponse
    {
        $statistics = $this->importService->getStatistics($import);

        return $this->json($statistics);
    }

    /**
     * @Rest\Get("/web-app/v1/imports/invalid-files/{id}")
     *
     *
     */
    public function getInvalidFile(Entity\ImportInvalidFile $importInvalidFile): BinaryFileResponse
    {
        $filename = $importInvalidFile->getFilename();
        $path = $this->importInvalidFilesDirectory . '/' . $filename;

        if (!file_exists($path)) {
            throw new RuntimeException('Requested file does not exist on server.');
        }

        $response = new BinaryFileResponse($path);

        $mimeTypeGuesser = new FileinfoMimeTypeGuesser();
        if ($mimeTypeGuesser->isGuesserSupported()) {
            $response->headers->set('Content-Type', $mimeTypeGuesser->guessMimeType($path));
        } else {
            $response->headers->set('Content-Type', 'text/plain');
        }

        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);

        return $response;
    }

    /**
     * @Rest\Get("/web-app/v1/imports/{id}/invalid-files")
     *
     *
     */
    public function listInvalidFiles(Entity\Import $import): JsonResponse
    {
        $invalidFiles = $this->managerRegistry->getRepository(Entity\ImportInvalidFile::class)
            ->findBy([
                'import' => $import,
            ], ['createdAt' => 'desc']);

        return $this->json(new Paginator($invalidFiles));
    }

    /**
     * @Rest\Get("/web-app/v1/imports/queue/{id}")
     *
     *
     */
    public function queueItem(Entity\ImportQueue $importQueue): JsonResponse
    {
        return $this->json($importQueue);
    }

    /**
     * @Rest\Patch("/web-app/v1/imports/queue/{id}")
     *
     *
     *
     * @return JsonResponse
     */
    public function singleDuplicityResolve(
        Entity\ImportQueue $importQueue,
        Import\Duplicity\ResolveSingleDuplicityInputType $inputType
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $this->importService->resolveDuplicity($importQueue, $inputType, $user);

        return new Response('', Response::HTTP_ACCEPTED);
    }

    /**
     * @Rest\Patch("/web-app/v1/imports/{id}/duplicities")
     *
     *
     * @return JsonResponse
     */
    public function allDuplicitiesResolve(
        Entity\Import $import,
        Import\Duplicity\ResolveAllDuplicitiesInputType $inputType
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $this->importService->resolveAllDuplicities($import, $inputType, $user);

        return new Response('', Response::HTTP_ACCEPTED);
    }

    /**
     * @Rest\Get("/web-app/v1/imports/{id}/queue")
     *
     *
     */
    public function listQueue(Entity\Import $import): JsonResponse
    {
        $importQueue = $this->importQueueRepo->findBy(['import' => $import]);

        return $this->json(new Paginator($importQueue));
    }

    /**
     * @Rest\Get("/web-app/v1/imports/{id}/fails")
     *
     *
     */
    public function failedList(Entity\Import $import, ImportLineFactory $lineFactory): JsonResponse
    {
        $importQueues = $this->importQueueRepo->findBy([
            'import' => $import,
            'state' => ImportQueueState::ERROR,
        ]);

        $fails = array_values(
            array_map(function (Entity\ImportQueue $failedQueue) use ($lineFactory) {
                $line = $lineFactory->create($failedQueue, 0);
                $messages = json_decode($failedQueue->getMessage(), true, 512, JSON_THROW_ON_ERROR);

                $householdId = null;
                $householdHeadId = null;
                if ($failedQueue->getAcceptedDuplicity()) {
                    $household = $failedQueue->getAcceptedDuplicity()->getTheirs();
                    $householdId = $household->getId();
                    $householdHeadId = $household->getHouseholdHead()->getId();
                }

                return [
                    "id" => $failedQueue->getId(),
                    "householdId" => $householdId,
                    "beneficiaryId" => $householdHeadId,
                    "failedAction" => $messages[-1]['action'],
                    "errorMessage" => $messages[-1]['message'],
                    "localFamilyName" => $line->localFamilyName,
                    "localGivenName" => $line->localGivenName,
                    "localParentsName" => $line->localParentsName,
                    "enFamilyName" => $line->englishFamilyName,
                    "enGivenName" => $line->englishGivenName,
                    "enParentsName" => $line->englishParentsName,
                    "primaryIdCard" => [
                        "number" => $line->primaryIdNumber,
                        "type" => $line->primaryIdType,
                    ],
                ];
            }, $importQueues)
        );

        return $this->json(new Paginator($fails));
    }
}
