<?php

namespace Controller\SupportApp\Smartcard;


use Controller\AbstractController;
use Doctrine\ORM\ORMException;
use Entity\Smartcard;
use FOS\RestBundle\Controller\Annotations as Rest;
use InputType\Smartcard\UpdateSmartcardInputType;
use Repository\SmartcardRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Utils\SmartcardService;

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
     * @throws ORMException
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
     * @throws ORMException
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
     * @return JsonnResponse
     * @throws ORMException
     */
    public function smartcardDeposits(string $smartcardCode):JsonResponse
    {
        $smartcard = $this->smartcardService->getSmartcardByCode($smartcardCode);
        $purchases = $smartcard->getDeposites();
        return $this->json($purchases);
    }

    /**
     * @Rest\Patch("/{id}")
     *
     * @param Smartcard                   $smartcard
     * @param UpdateSmartcardInputType $updateSmartcardInputType
     * @param SmartcardService         $smartcardService
     *
     * @return JsonResponse
     * @throws \Component\Smartcard\Exception\SmartcardActivationDeactivatedException
     * @throws \Component\Smartcard\Exception\SmartcardNotAllowedStateTransition
     * @throws ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function update(
        Smartcard                   $smartcard,
        UpdateSmartcardInputType $updateSmartcardInputType,
        SmartcardService         $smartcardService ): JsonResponse {

        $user = $this->tokenStorage->getToken()->getUser();

        if ($user->hasRole('ROLE_ADMIN')) {

            $smartcard = $this->smartcardRepository->find($smartcard);
            $smartcard = $smartcardService->update($smartcard, $updateSmartcardInputType);

            return $this->json($smartcard);
        }else{
            throw new AccessDeniedException('You do not have the privilege to update the Smartcard');
        }

    }
}
