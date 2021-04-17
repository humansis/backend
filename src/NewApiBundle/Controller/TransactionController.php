<?php

declare(strict_types=1);

namespace NewApiBundle\Controller;

use BeneficiaryBundle\Entity\Beneficiary;
use CommonBundle\Pagination\Paginator;
use DistributionBundle\Entity\Assistance;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\Component\Codelist\CodeLists;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use TransactionBundle\Entity\Transaction;

class TransactionController extends AbstractController
{
    /**
     * @Rest\Get("/assistances/{assistanceId}/beneficiaries/{beneficiaryId}/transactions")
     * @ParamConverter("assistance", options={"mapping": {"assistanceId" : "id"}})
     * @ParamConverter("beneficiary", options={"mapping": {"beneficiaryId" : "id"}})
     *
     * @param Assistance  $assistance
     * @param Beneficiary $beneficiary
     *
     * @return JsonResponse
     */
    public function byAssistanceAndBeneficiary(Assistance $assistance, Beneficiary $beneficiary): JsonResponse
    {
        try {
            $this->get('transaction.transaction_service')->updateTransactionStatus($assistance->getProject()->getIso3(), $assistance);
        } catch (\Exception $exception) {
            $this->get('logger')->addCritical($exception->getMessage());
        }

        $list = $this->getDoctrine()->getRepository(Transaction::class)
            ->findByAssistanceBeneficiary($assistance, $beneficiary);

        return $this->json(new Paginator($list));
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
        $this->forward(\TransactionBundle\Controller\TransactionController::class.'::sendTransactionAction', [$request, $assistance]);

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
