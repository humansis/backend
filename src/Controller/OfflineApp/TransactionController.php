<?php

declare(strict_types=1);

namespace Controller\OfflineApp;

use Doctrine\Persistence\ManagerRegistry;
use FOS\RestBundle\Controller\Annotations as Rest;
use Controller\AbstractController;
use InputType\TransactionFilterInputType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Entity\Transaction;
use Repository\TransactionRepository;

class TransactionController extends AbstractController
{
    public function __construct(private readonly ManagerRegistry $managerRegistry)
    {
    }
    #[Rest\Get('/offline-app/v1/transactions')]
    public function list(Request $request, TransactionFilterInputType $filter): JsonResponse
    {
        /** @var TransactionRepository $repository */
        $repository = $this->managerRegistry->getRepository(Transaction::class);
        $data = $repository->findByParams($filter);

        $response = $this->json($data);
        $response->setEtag(md5($response->getContent()));
        $response->setPublic();
        $response->isNotModified($request);

        return $response;
    }
}
