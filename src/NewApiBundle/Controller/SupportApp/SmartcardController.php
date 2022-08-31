<?php

namespace NewApiBundle\Controller\SupportApp;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\Component\Smartcard\Exception\SmartcardActivationDeactivatedException;
use NewApiBundle\Controller\AbstractController;
use NewApiBundle\InputType\Smartcard\ChangeSmartcardInputType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use VoucherBundle\Repository\SmartcardRepository;
use VoucherBundle\Utils\SmartcardService;

class SmartcardController extends AbstractController
{
    /**
     * @var SmartcardRepository
     */
    private $smartcardRepository;

    public function __construct(
        SmartcardRepository $smartcardRepository
    )
    {
        $this->smartcardRepository = $smartcardRepository;
    }

    /**
     * @Rest\Get("/support-app/v1/smartcards/{smartcardCode}")
     *
     * @param string $smartcardCode
     *
     * @return JsonResponse
     */
    public function smartcard(string $smartcardCode):JsonResponse
    {
        $smartcard = $this->smartcardRepository->findOneBy(['serialNumber' => $smartcardCode]);

        return $this->json($smartcard);
    }

    /**
     * @Rest\Get ("/support-app/v1/smartcards/{smartcardCode}/purchases")
     *
     * @param string $smartcardCode
     *
     * @return JsonResponse
     */
     public function smartcardPurchases(string $smartcardCode):JsonResponse
     {
         $purchases = $this->smartcardRepository->findOneBy(['serialNumber' => $smartcardCode])->getPurchases();

         return $this->json($purchases);
     }

    /**
     * @Rest\Get ("/support-app/v1/smartcards/{smartcardCode}/deposits")
     *
     * @param string $smartcardCode
     *
     * @return JsonResponse
     */
    public function smartcardDeposits(string $smartcardCode):JsonResponse
    {
        $purchases = $this->smartcardRepository->findOneBy(['serialNumber' => $smartcardCode])->getDeposites();

        return $this->json($purchases);
    }

    /**
     * @Rest\Patch("/support-app/v1/smartcards/{serialNumber}")
     *
     * @param string                   $serialNumber
     * @param ChangeSmartcardInputType $changeSmartcardInputType
     * @param SmartcardRepository      $smartcardRepository
     * @param SmartcardService         $smartcardService
     *
     * @return Response
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function change(
        string                   $serialNumber,
        ChangeSmartcardInputType $changeSmartcardInputType,
        SmartcardService         $smartcardService
    ): Response {
        $smartcard = $this->smartcardRepository->findOneBy(['serialNumber' => $serialNumber]);
        try {
            $smartcardService->change($smartcard, $changeSmartcardInputType);

            return Response::create();
        } catch (SmartcardActivationDeactivatedException|SmartcardNotAllowedStateTransition $e) {
            return Response::create('', Response::HTTP_ACCEPTED);
        }
    }
}
