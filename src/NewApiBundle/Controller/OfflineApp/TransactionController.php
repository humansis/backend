<?php

declare(strict_types=1);

namespace NewApiBundle\Controller\OfflineApp;

use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\Controller\AbstractController;
use NewApiBundle\InputType\TransactionFilterInputType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use TransactionBundle\Entity\Transaction;
use TransactionBundle\Repository\TransactionRepository;

class TransactionController extends AbstractController
{
    /**
     * @Rest\Get("/offline-app/v1/transactions")
     *
     * @param Request                    $request
     * @param TransactionFilterInputType $filter
     *
     * @return JsonResponse
     */
    public function list(Request $request, TransactionFilterInputType $filter): JsonResponse
    {
        /** @var TransactionRepository $repository */
        $repository = $this->getDoctrine()->getRepository(Transaction::class);
        $data = $repository->findByParams($filter);

        return $this->json($data);
    }

}
