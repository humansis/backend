<?php
declare(strict_types=1);

namespace NewApiBundle\Controller;

use CommonBundle\Controller\ExportController;
use CommonBundle\Pagination\Paginator;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\Component\Import\ImportService;
use NewApiBundle\Component\Import\UploadImportService;
use NewApiBundle\Entity;
use NewApiBundle\Enum\ImportState;
use NewApiBundle\InputType\DuplicityResolveInputType;
use NewApiBundle\InputType\Import;
use NewApiBundle\Request\Pagination;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Mime\FileinfoMimeTypeGuesser;
use UserBundle\Entity\User;

class ImportController extends AbstractController
{
    const DISABLE_CRON = 'disable-cron-fast-forward';

    /**
     * @var ImportService
     */
    private $importService;

    /**
     * @var UploadImportService
     */
    private $uploadImportService;

    /**
     * @var string
     */
    private $importInvalidFilesDirectory;

    /**
     * @var int
     */
    private $maxFileSizeToLoad;

    public function __construct(ImportService $importService, UploadImportService $uploadImportService, string $importInvalidFilesDirectory, int $maxFileSizeToLoad)
    {
        $this->importService = $importService;
        $this->uploadImportService = $uploadImportService;
        $this->importInvalidFilesDirectory = $importInvalidFilesDirectory;
        $this->maxFileSizeToLoad = $maxFileSizeToLoad;
    }

    /**
     * @Rest\Get("/web-app/v1/imports/template")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function template(Request $request): Response
    {
        $request->query->add(['householdsTemplate' => true]);
        $request->request->add(['__country' => $request->headers->get('country')]);

        return $this->forward(ExportController::class.'::exportAction', [], $request->query->all());
    }

    /**
     * @Rest\Get("/web-app/v1/imports/{id}")
     *
     * @param Entity\Import $institution
     *
     * @return JsonResponse
     */
    public function item(Entity\Import $institution): JsonResponse
    {
        return $this->json($institution);
    }

    /**
     * @Rest\Get("/web-app/v1/imports")
     *
     * @param Pagination            $pagination
     * @param Import\FilterInputType $filterInputType
     * @param Import\OrderInputType  $orderInputType
     *
     * @return JsonResponse
     */
    public function list(Pagination $pagination, Import\FilterInputType $filterInputType, Import\OrderInputType $orderInputType, Request $request): JsonResponse
    {
        $data = $this->getDoctrine()->getRepository(Entity\Import::class)
            ->findByParams($request->headers->get('country'), $pagination, $filterInputType, $orderInputType);

        return $this->json($data);
    }

    /**
     * @Rest\Post("/web-app/v1/imports")
     *
     * @param Import\CreateInputType $inputType
     *
     * @return JsonResponse
     */
    public function create(Request $request, Import\CreateInputType $inputType): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $institution = $this->importService->create($request->headers->get('country'), $inputType, $user);

        return $this->json($institution);
    }

    /**
     * @Rest\Patch("/web-app/v1/imports/{id}")
     *
     * @param Entity\Import               $import
     * @param Import\PatchInputType $inputType
     *
     * @return JsonResponse
     * @throws \Exception
     */
    public function updateStatus(Request $request, Entity\Import $import, Import\PatchInputType $inputType): JsonResponse
    {
        $this->importService->patch($import, $inputType);

        if ($request->get(self::DISABLE_CRON, false) === true) {
            return $this->json(null, Response::HTTP_ACCEPTED);
        }

        $kernel = $this->get('kernel');
        $application = new Application($kernel);
        $application->setAutoExit(false);

        $output = new BufferedOutput();
        if ($import->getState() === ImportState::INTEGRITY_CHECKING) {
            $command = new ArrayInput([
                'command' => 'app:import:integrity',
                'import' => $import->getId(),
            ]);
            $application->run($command, $output);
            $application->run($command, $output);
        }
        if ($import->getState() === ImportState::IDENTITY_CHECKING) {
            $command = new ArrayInput([
                'command' => 'app:import:identity',
                'import' => $import->getId(),
            ]);
            $application->run($command, $output);
            $application->run($command, $output);
        }
        if ($import->getState() === ImportState::SIMILARITY_CHECKING) {
            $command = new ArrayInput([
                'command' => 'app:import:similarity',
                'import' => $import->getId(),
            ]);
            $application->run($command, $output);
            $application->run($command, $output);
        }
        if ($import->getState() === ImportState::IMPORTING && $import->getImportQueue()->count() <= ImportService::ASAP_LIMIT) {
            $application->run(new ArrayInput([
                'command' => 'app:import:finish',
                'import' => $import->getId(),
            ]), $output);
        }

        return $this->json(null, Response::HTTP_ACCEPTED);
    }

    /**
     * @Rest\Get("/web-app/v1/imports/{id}/files")
     *
     * @param Entity\Import $import
     *
     * @return JsonResponse
     */
    public function listFiles(Entity\Import $import): JsonResponse
    {
        $data = $this->getDoctrine()->getRepository(Entity\ImportFile::class)
            ->findBy([
                'import' => $import,
            ], [
                'createdAt' => 'DESC',
            ]);

        return $this->json(New Paginator($data));
    }

    /**
     * @Rest\Post("/web-app/v1/imports/{id}/files")
     *
     * @param Entity\Import  $import
     *
     * @param Request $request
     *BinaryFileResponse
     * @return JsonResponse
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function uploadFile(Entity\Import $import, Request $request): JsonResponse
    {
        if (!in_array($import->getState(), [
            ImportState::NEW,
            ImportState::INTEGRITY_CHECKING,
            ImportState::INTEGRITY_CHECK_CORRECT,
            ImportState::INTEGRITY_CHECK_FAILED,
        ])) {
            throw new \InvalidArgumentException('You cannot upload file to this import.');
        }

        /** @var UploadedFile[] $files */
        $files = $request->files->all();

        if (empty($files)) {
            throw new \InvalidArgumentException('Missing at least one upload file.');
        }

        /** @var User $user */
        $user = $this->getUser();

        $importFiles = [];
        foreach ($files as $file) {
            $fileSize = $file->getSize();

            $importFiles[] = $uploadedFile = $this->uploadImportService->uploadFile($import, $file, $user);

            if ($fileSize < $this->maxFileSizeToLoad * 1024 * 1024 && empty($uploadedFile->getStructureViolations())) {
                $this->uploadImportService->load($uploadedFile);
            }
        }

        return $this->json(new Paginator($importFiles));
    }

    /**
     * @Rest\Delete("/web-app/v1/imports/files/{id}")
     *
     * @param Entity\ImportFile $importFile
     *
     * @return JsonResponse
     */
    public function deleteFile(Entity\ImportFile $importFile): JsonResponse
    {
        if (!in_array($importFile->getImport()->getState(), [
            ImportState::INTEGRITY_CHECKING,
            ImportState::INTEGRITY_CHECK_CORRECT,
            ImportState::INTEGRITY_CHECK_FAILED,
        ])) {
            throw new \InvalidArgumentException('You cannot delete file from this import.');
        }

        $this->importService->removeFile($importFile);

        return $this->json(null, Response::HTTP_ACCEPTED);
    }

    /**
     * @Rest\Get("/web-app/v1/imports/{id}/duplicities")
     *
     * @param Entity\Import $import
     *
     * @return JsonResponse
     */
    public function duplicities(Entity\Import $import): JsonResponse
    {
        /** @var Entity\ImportHouseholdDuplicity[] $duplicities */
        $duplicities = $this->getDoctrine()->getRepository(Entity\ImportHouseholdDuplicity::class)
            ->findByImport($import);

        return $this->json($duplicities);
    }

    /**
     * @Rest\Get("/web-app/v1/imports/{id}/statistics")
     *
     * @param Entity\Import $import
     *
     * @return JsonResponse
     */
    public function queueProgress(Entity\Import $import): JsonResponse
    {
        $statistics = $this->importService->getStatistics($import);

        return $this->json($statistics);
    }

    /**
     * @Rest\Get("/web-app/v1/imports/invalid-files/{id}")
     *
     * @param Entity\ImportInvalidFile $importInvalidFile
     *
     * @return BinaryFileResponse
     */
    public function getInvalidFile(Entity\ImportInvalidFile $importInvalidFile): BinaryFileResponse
    {
        $filename = $importInvalidFile->getFilename();
        $path = $this->importInvalidFilesDirectory.'/'.$filename;

        if (!file_exists($path)) {
            throw new \RuntimeException('Requested file does not exist on server.');
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
     * @param Entity\Import $import
     *
     * @return JsonResponse
     */
    public function listInvalidFiles(Entity\Import $import): JsonResponse
    {
        $invalidFiles = $this->getDoctrine()->getRepository(Entity\ImportInvalidFile::class)
            ->findBy([
                'import' => $import,
            ]);

        return $this->json(new Paginator($invalidFiles));
    }

    /**
     * @Rest\Get("/web-app/v1/imports/queue/{id}")
     *
     * @param Entity\ImportQueue $importQueue
     *
     * @return JsonResponse
     */
    public function queueItem(Entity\ImportQueue $importQueue): JsonResponse
    {
        return $this->json($importQueue);
    }

    /**
     * @Rest\Patch("/web-app/v1/imports/queue/{id}")
     *
     * @param Entity\ImportQueue               $importQueue
     *
     * @param Import\Duplicity\ResolveSingleDuplicityInputType $inputType
     *
     * @return JsonResponse
     */
    public function duplicityResolve(Entity\ImportQueue $importQueue, Import\Duplicity\ResolveSingleDuplicityInputType $inputType): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $this->importService->resolveDuplicity($importQueue, $inputType, $user);

        return $this->json(null, Response::HTTP_ACCEPTED);
    }

    /**
     * @Rest\Get("/web-app/v1/imports/{id}/queue")
     *
     * @param Entity\Import $import
     *
     * @return JsonResponse
     */
    public function listQueue(Entity\Import $import): JsonResponse
    {
        $importQueue = $this->getDoctrine()->getRepository(Entity\ImportQueue::class)
            ->findBy([
                'import' => $import,
            ]);

        return $this->json(new Paginator($importQueue));
    }
}
