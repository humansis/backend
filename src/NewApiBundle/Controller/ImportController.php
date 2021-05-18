<?php
declare(strict_types=1);

namespace NewApiBundle\Controller;

use CommonBundle\Pagination\Paginator;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\Entity\Import;
use NewApiBundle\Entity\ImportFile;
use NewApiBundle\InputType\ImportCreateInputType;
use NewApiBundle\InputType\ImportUpdateStatusInputType;
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
     * @return JsonResponse
     */
    public function list(): JsonResponse
    {
        $data = $this->getDoctrine()->getRepository(Import::class)
            ->findAll();

        return $this->json(New Paginator($data));
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
        $this->get('service.import')->removeFile($importFile);

        return $this->json(null, Response::HTTP_ACCEPTED);
    }
}
