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

/**
 * @Rest\Route("/support-app/v1/smartcards")
 */
class SmartcardController extends AbstractController
{

    /**
     * @var SmartcardService
     */
    private $smartcardService;

    /**
     * @var SmartcardRepository
     */
    private $smartcardRepository;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @param SmartcardService      $smartcardService
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(
        SmartcardService $smartcardService,
        SmartcardRepository $smartcardRepository,
        TokenStorageInterface  $tokenStorage
    )
    {
        $this->smartcardService = $smartcardService;
        $this->smartcardRepository = $smartcardRepository;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @Rest\Get("/{smartcardCode}")
     *
     * @param string $smartcardCode
     *
     * @return JsonResponse
     * @throws \Doctrine\ORM\ORMException
     */
    public function smartcard(string $smartcardCode):JsonResponse
    {
        $smartcards = $this->smartcardRepository->findBy(['serialNumber' => $smartcardCode]);
        return $this->json(['data' => $smartcards]);
    }

    /**
     * @Rest\Get ("/{smartcardCode}/purchases")
     *
     * @param string $smartcardCode
     *
     * @return JsonResponse
     * @throws \Doctrine\ORM\ORMException
     */
     public function smartcardPurchases(string $smartcardCode):JsonResponse
     {
         $smartcard = $this->smartcardService->getSmartcardByCode($smartcardCode);
         $purchases = $smartcard->getPurchases();
         return $this->json($purchases);
     }

    /**
     * @Rest\Get ("/{smartcardCode}/deposits")
     *
     * @param string $smartcardCode
     *
     * @return JsonResponse
     * @throws \Doctrine\ORM\ORMException
     */
    public function smartcardDeposits(string $smartcardCode):JsonResponse
    {
        $smartcard = $this->smartcardService->getSmartcardByCode($smartcardCode);
        $purchases = $smartcard->getDeposites();
        return $this->json($purchases);
    }

    /**
     * @Rest\Patch("/{serialNumber}")
     *
     * @param string                   $serialNumber
     * @param UpdateSmartcardInputType $updateSmartcardInputType
     * @param SmartcardService         $smartcardService
     *
     * @return JsonResponse
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \NewApiBundle\Component\Smartcard\Exception\SmartcardActivationDeactivatedException
     * @throws \NewApiBundle\Component\Smartcard\Exception\SmartcardNotAllowedStateTransition
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