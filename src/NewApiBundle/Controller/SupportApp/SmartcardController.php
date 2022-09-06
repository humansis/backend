<?php

namespace NewApiBundle\Controller\SupportApp;


use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\Controller\AbstractController;
use NewApiBundle\InputType\Smartcard\UpdateSmartcardInputType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use VoucherBundle\Repository\SmartcardRepository;
use VoucherBundle\Utils\SmartcardService;


class SmartcardController extends AbstractController
{
    /**
     * @var SmartcardRepository
     */
    private $smartcardRepository;

    /**
     * @var SmartcardService
     */
    private $smartcardService;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @param SmartcardRepository   $smartcardRepository
     * @param SmartcardService      $smartcardService
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(
        SmartcardRepository $smartcardRepository,
        SmartcardService $smartcardService,
        TokenStorageInterface  $tokenStorage
    )
    {
        $this->smartcardRepository = $smartcardRepository;
        $this->smartcardService = $smartcardService;
        $this->tokenStorage = $tokenStorage;
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
        $smartcard = $this->smartcardService->getSmartcardByCode($smartcardCode);
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
         $smartcard = $this->smartcardService->getSmartcardByCode($smartcardCode);
         $purchases = $smartcard->getPurchases();
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
        $smartcard = $this->smartcardService->getSmartcardByCode($smartcardCode);
        $purchases = $smartcard->getDeposites();
        return $this->json($purchases);
    }

    /**
     * @Rest\Patch("/support-app/v1/smartcards/{serialNumber}")
     *
     * @param string                   $serialNumber
     * @param UpdateSmartcardInputType $updateSmartcardInputType
     * @param SmartcardRepository      $smartcardRepository
     * @param SmartcardService         $smartcardService
     *
     * @return JsonResponse
     */
    public function update(
        string                   $serialNumber,
        UpdateSmartcardInputType $updateSmartcardInputType,
        SmartcardService         $smartcardService ): JsonResponse {

        $user = $this->tokenStorage->getToken()->getUser();

        if ($user->hasRole('ROLE_ADMIN')) {

            $smartcard = $this->smartcardService->getSmartcardByCode($serialNumber);
            $smartcard = $smartcardService->update($smartcard, $updateSmartcardInputType);

            return $this->json($smartcard);
        }else{
            throw new AccessDeniedException('You do not have the privilege to update the Smartcard');
        }

    }
}
