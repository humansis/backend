<?php
declare(strict_types=1);

namespace NewApiBundle\Controller;

use CommonBundle\Pagination\Paginator;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\Entity\Import;
use NewApiBundle\InputType\ImportCreateInputType;
use Symfony\Component\HttpFoundation\JsonResponse;
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
}
