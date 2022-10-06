<?php

declare(strict_types=1);

namespace Controller\OfflineApp;

use FOS\RestBundle\Controller\Annotations as Rest;
use Controller\AbstractController;
use InputType\TransactionFilterInputType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Entity\Transaction;
use Repository\TransactionRepository;

class TransactionController extends AbstractController
{
    /**
     * @Rest\Get("/offline-app/v1/transactions")
     *
     * @param Request $request
     * @param TransactionFilterInputType $filter
     *
     * @return JsonResponse
     */
    public function list(Request $request, TransactionFilterInputType $filter): JsonResponse
    {
        /** @var TransactionRepository $repository */
        $repository = $this->getDoctrine()->getRepository(Transaction::class);
        $data = $repository->findByParams($filter);

        $response = $this->json($data);
        $response->setEtag(md5($response->getContent()));
        $response->setPublic();
        $response->isNotModified($request);

        return $response;
    }
}
