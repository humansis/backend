<?php

declare(strict_types=1);

namespace NewApiBundle\Controller;

use CommonBundle\Pagination\Paginator;
use DistributionBundle\Entity\Assistance;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\Component\Codelist\CodeLists;
use NewApiBundle\InputType\SmartcardDepositFilterInputType;
use NewApiBundle\InputType\TransactionFilterInputType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use TransactionBundle\Entity\Transaction;
use TransactionBundle\Repository\TransactionRepository;
use VoucherBundle\Entity\SmartcardDeposit;
use VoucherBundle\Repository\SmartcardDepositRepository;

class SmartcardDepositController extends AbstractController
{
    /**
     * @Rest\Get("/web-app/v1/smartcard-deposits")
     *
     * @param SmartcardDepositFilterInputType $filter
     *
     * @return JsonResponse
     */
    public function list(SmartcardDepositFilterInputType $filter): JsonResponse
    {
        /** @var SmartcardDepositRepository $repository */
        $repository = $this->getDoctrine()->getRepository(SmartcardDeposit::class);
        $data = $repository->findByParams($filter);

        return $this->json($data);
    }
}
