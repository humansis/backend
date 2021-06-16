<?php

declare(strict_types=1);

namespace NewApiBundle\Controller;

use CommonBundle\Pagination\Paginator;
use DistributionBundle\Entity\Assistance;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\Component\Codelist\CodeLists;
use NewApiBundle\InputType\TransactionFilterInputType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use TransactionBundle\Entity\Transaction;
use TransactionBundle\Repository\TransactionRepository;

class TransactionController extends AbstractController
{
    /**
     * @Rest\Get("/transactions")
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

    /**
     * @Rest\Post("/assistances/{id}/transactions")
     *
     * @param Assistance $assistance
     * @param Request    $request
     *
     * @return JsonResponse
     */
    public function createTransactions(Assistance $assistance, Request $request): JsonResponse
    {
        $request->request->set('__country', $request->headers->get('country'));

        $this->forward(\TransactionBundle\Controller\TransactionController::class.'::sendTransactionAction', [
            'request' => $request,
            'assistance' => $assistance,
        ]);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @Rest\Post("/assistances/{id}/transactions/emails")
     * @ParamConverter("assistance", options={"mapping": {"id": "id"}})
     *
     * @param Assistance $assistance
     *
     * @return JsonResponse
     */
    public function sendEmail(Assistance $assistance): JsonResponse
    {
        $this->get('transaction.transaction_service')->sendVerifyEmail($this->getUser(), $assistance);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @Rest\Get("/transactions/statuses")
     *
     * @return JsonResponse
     */
    public function statuses(): JsonResponse
    {
        $data = CodeLists::mapArray(Transaction::statuses());

        return $this->json(new Paginator($data));
    }
}
