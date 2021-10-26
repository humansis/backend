<?php
declare(strict_types=1);

namespace NewApiBundle\Controller;

use CommonBundle\Pagination\Paginator;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\Entity\SynchronizationBatch;
use NewApiBundle\Enum\SynchronizationBatchState;
use NewApiBundle\Request\Pagination;
use Symfony\Component\HttpFoundation\JsonResponse;
use NewApiBundle\Repository\SynchronizationBatchRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SynchronizationBatchController extends AbstractController
{
    /**
     * @Rest\Get("/web-app/v1/syncs")
     *
     * @return JsonResponse
     */
    public function list(): JsonResponse
    {
        /** @var SynchronizationBatchRepository $repository */
        $repository = $this->getDoctrine()->getRepository(SynchronizationBatch::class);
        $data = $repository->findAll();

        return $this->json(new Paginator($data));
    }

    /**
     * @Rest\Get("/web-app/v1/syncs/{id}")
     *
     * @param SynchronizationBatch $object
     *
     * @return JsonResponse
     */
    public function detail(SynchronizationBatch $object): JsonResponse
    {
        if ($object->getState() === SynchronizationBatchState::ARCHIVED) {
            throw new NotFoundHttpException("Archived");
        }
        return $this->json($object);
    }
}
