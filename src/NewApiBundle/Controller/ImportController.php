<?php
declare(strict_types=1);

namespace NewApiBundle\Controller;

use CommonBundle\Pagination\Paginator;
use FOS\RestBundle\Controller\Annotations as Rest;
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
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use UserBundle\Entity\User;

class ImportController extends AbstractController
{
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

        $institution = $this->get('service.import')->create($inputType, $user);

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
        $this->get('service.import')->updateStatus($import, $inputType);

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

        /** @var UploadedFile|null $file */
        $file = $request->files->get('files');

        if (is_null($file)) {
            throw new \InvalidArgumentException('Missing upload file');
        }

        /** @var User $user */
        $user = $this->getUser();

        $importFile = $this->get('service.upload_import')->upload($import, $file, $user);

        return $this->json($importFile);
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

        $this->get('service.import')->removeFile($importFile);

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
     * @Rest\Get("/imports/{id}/queue-progress")
     *
     * @param Import $import
     *
     * @return JsonResponse
     */
    public function queueProgress(Import $import): JsonResponse
    {
        $queueProgress = $this->get('service.import')->getQueueProgress($import);

        return $this->json($queueProgress);
    }

    /**
     * @Rest\Get("/imports/{id}/invalid-files")
     *
     * @param Import $import
     */
    public function invalidFiles(Import $import)
    {
        //TODO implement invalid files logic
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

        $this->get('service.import')->resolveDuplicity($importQueue, $inputType, $user);

        return $this->json(null, Response::HTTP_ACCEPTED);
    }
}
