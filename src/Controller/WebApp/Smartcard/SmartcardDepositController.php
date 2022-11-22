<?php

declare(strict_types=1);

namespace Controller\WebApp\Smartcard;

use FOS\RestBundle\Controller\Annotations as Rest;
use Controller\WebApp\AbstractWebAppController;
use InputType\SmartcardDepositFilterInputType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Entity\SmartcardDeposit;
use Repository\SmartcardDepositRepository;

class SmartcardDepositController extends AbstractWebAppController
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
