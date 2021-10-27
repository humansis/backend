<?php
declare(strict_types=1);

namespace NewApiBundle\Controller;

use CommonBundle\InputType\Country;
use CommonBundle\Pagination\Paginator;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\Entity\SynchronizationBatch;
use NewApiBundle\Enum\SynchronizationBatchState;
use NewApiBundle\Request\Pagination;
use Symfony\Component\HttpFoundation\JsonResponse;
use NewApiBundle\Repository\SynchronizationBatchRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use NewApiBundle\InputType\SynchronizationBatchFilterInputType;
use NewApiBundle\InputType\SynchronizationBatchOrderInputType;

class SynchronizationBatchController extends AbstractController
{
    /**
     * @Rest\Get("/web-app/v1/syncs")
     *
     * @param SynchronizationBatchFilterInputType $filter
     * @param SynchronizationBatchOrderInputType  $orderBy
     * @param Pagination                          $pagination
     *
     * @return JsonResponse
     */
    public function list(SynchronizationBatchFilterInputType $filter, SynchronizationBatchOrderInputType $orderBy, Pagination $pagination): JsonResponse
    {
        /** @var SynchronizationBatchRepository $repository */
        $repository = $this->getDoctrine()->getRepository(SynchronizationBatch::class);
        $data = $repository->findByParams(null, $pagination, $filter, $orderBy);

        return $this->json($data);
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
