<?php
declare(strict_types=1);

namespace NewApiBundle\Controller;

use CommonBundle\Pagination\Paginator;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\Component\Import\ImportInvalidFileService;
use NewApiBundle\Component\Import\ImportService;
use NewApiBundle\Component\Import\UploadImportService;
use NewApiBundle\Entity\Import;
use NewApiBundle\Entity\ImportBeneficiaryDuplicity;
use NewApiBundle\Entity\ImportFile;
use NewApiBundle\Entity\ImportQueue;
use NewApiBundle\Enum\ImportState;
use NewApiBundle\InputType\DuplicityResolveInputType;
use NewApiBundle\InputType\ImportCreateInputType;
use NewApiBundle\InputType\ImportFilterInputType;
use NewApiBundle\InputType\ImportOrderInputType;
use NewApiBundle\InputType\ImportUpdateStatusInputType;
use NewApiBundle\Request\Pagination;
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
    /**
     * @var ImportService
     */
    private $importService;

    /**
     * @var UploadImportService
     */
    private $uploadImportService;

    /**
     * @var ImportInvalidFileService
     */
    private $importInvalidFileService;

    public function __construct(ImportService $importService, UploadImportService $uploadImportService, ImportInvalidFileService $importInvalidFileService)
    {
        $this->importService = $importService;
        $this->uploadImportService = $uploadImportService;
        $this->importInvalidFileService = $importInvalidFileService;
    }

    /**
     * @Rest\Get("/imports/{id}")
     *
     * @param Import $institution
     *
     * @return JsonResponse
     */
    public function item(Import $institution): JsonResponse
    {
        return $this->json($institution);
    }

    /**
     * @Rest\Get("/imports")
     *
     * @param Pagination            $pagination
     * @param ImportFilterInputType $filterInputType
     * @param ImportOrderInputType  $orderInputType
     *
     * @return JsonResponse
     */
    public function list(Pagination $pagination, ImportFilterInputType $filterInputType, ImportOrderInputType $orderInputType): JsonResponse
    {
        $data = $this->getDoctrine()->getRepository(Import::class)
            ->findByParams($pagination, $filterInputType, $orderInputType);

        return $this->json($data);
    }

    /**
     * @Rest\Post("/imports")
     *
     * @param ImportCreateInputType $inputType
     *
     * @return JsonResponse
     */
    public function create(ImportCreateInputType $inputType): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $institution = $this->importService->create($inputType, $user);

        return $this->json($institution);
    }

    /**
     * @Rest\Patch("/imports/{id}")
     *
     * @param Import                      $import
     * @param ImportUpdateStatusInputType $inputType
     *
     * @return JsonResponse
     */
    public function updateStatus(Import $import, ImportUpdateStatusInputType $inputType): JsonResponse
    {
        $this->importService->updateStatus($import, $inputType);

        return $this->json(null, Response::HTTP_ACCEPTED);
    }

    /**
     * @Rest\Get("/imports/{id}/files")
     *
     * @param Import $import
     *
     * @return JsonResponse
     */
    public function listFiles(Import $import): JsonResponse
    {
        $data = $this->getDoctrine()->getRepository(ImportFile::class)
            ->findBy([
                'import' => $import,
            ]);

        return $this->json(New Paginator($data));
    }

    /**
     * @Rest\Post("/imports/{id}/files")
     *
     * @param Import  $import
     *
     * @param Request $request
     *
     * @return JsonResponse
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function uploadFile(Import $import, Request $request): JsonResponse
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
            $importFiles[] = $this->uploadImportService->upload($import, $file, $user);
        }

        return $this->json(new Paginator($importFiles));
    }

    /**
     * @Rest\Delete("/imports/files/{id}")
     *
     * @param ImportFile $importFile
     *
     * @return JsonResponse
     */
    public function deleteFile(ImportFile $importFile): JsonResponse
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
     * @Rest\Get("/imports/{id}/duplicities")
     *
     * @param Import $import
     *
     * @return JsonResponse
     */
    public function duplicities(Import $import): JsonResponse
    {
        /** @var ImportBeneficiaryDuplicity[] $duplicities */
        $duplicities = $this->getDoctrine()->getRepository(ImportBeneficiaryDuplicity::class)
            ->findByImport($import);

        return $this->json($duplicities);
    }

    /**
     * @Rest\Get("/imports/{id}/statistics")
     *
     * @param Import $import
     *
     * @return JsonResponse
     */
    public function queueProgress(Import $import): JsonResponse
    {
        $statistics = $this->importService->getStatistics($import);

        return $this->json($statistics);
    }

    /**
     * @Rest\Get("/imports/{id}/invalid-files")
     *
     * @param Import $import
     *
     * @return BinaryFileResponse
     */
    public function invalidFiles(Import $import): BinaryFileResponse
    {
        $filepath = $this->importInvalidFileService->generateInvalidFilePath($import);

        if (!file_exists($filepath)) {
            throw $this->createNotFoundException();
        }

        $response = new BinaryFileResponse($filepath);

        $mimeTypeGuesser = new FileinfoMimeTypeGuesser();
        if ($mimeTypeGuesser->isGuesserSupported()) {
            $response->headers->set('Content-Type', $mimeTypeGuesser->guessMimeType($filepath));
        } else {
            $response->headers->set('Content-Type', 'text/plain');
        }

        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $import->getTitle().'-invalid-entries.xlsx');

        return $response;
    }

    /**
     * @Rest\Get("/imports/queue/{id}")
     *
     * @param ImportQueue $importQueue
     *
     * @return JsonResponse
     */
    public function queueItem(ImportQueue $importQueue): JsonResponse
    {
        return $this->json($importQueue);
    }

    /**
     * @Rest\Patch("/imports/queue/{id}")
     *
     * @param ImportQueue               $importQueue
     *
     * @param DuplicityResolveInputType $inputType
     *
     * @return JsonResponse
     */
    public function duplicityResolve(ImportQueue $importQueue, DuplicityResolveInputType $inputType): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $this->importService->resolveDuplicity($importQueue, $inputType, $user);

        return $this->json(null, Response::HTTP_ACCEPTED);
    }
}
