<?php

namespace Controller\SupportApp\Smartcard;

use Component\Smartcard\Exception\SmartcardActivationDeactivatedException;
use Component\Smartcard\Exception\SmartcardNotAllowedStateTransition;
use Controller\AbstractController;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Entity\Smartcard;
use Enum\RoleType;
use FOS\RestBundle\Controller\Annotations as Rest;
use InputType\Smartcard\UpdateSmartcardInputType;
use Repository\RoleRepository;
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
    public function __construct(
        private readonly SmartcardService $smartcardService,
        private readonly SmartcardRepository $smartcardRepository,
        private readonly RoleRepository $roleRepository,
        private readonly TokenStorageInterface $tokenStorage
    ) {
    }

    /**
     * @Rest\Get("/{smartcardCode}")
     *
     *
     * @throws ORMException
     */
    public function smartcard(string $smartcardCode): JsonResponse
    {
        $smartcards = $this->smartcardRepository->findBy(['serialNumber' => $smartcardCode]);

        return $this->json(['data' => $smartcards]);
    }

    /**
     * @Rest\Get ("/{smartcardCode}/purchases")
     *
     *
     * @throws ORMException
     */
    public function smartcardPurchases(string $smartcardCode): JsonResponse
    {
        $smartcard = $this->smartcardService->getSmartcardByCode($smartcardCode);
        $purchases = $smartcard->getPurchases();

        return $this->json($purchases);
    }

    /**
     * @Rest\Get ("/{smartcardCode}/deposits")
     *
     *
     * @return JsonnResponse
     * @throws ORMException
     */
    public function smartcardDeposits(string $smartcardCode): JsonResponse
    {
        $smartcard = $this->smartcardService->getSmartcardByCode($smartcardCode);
        $purchases = $smartcard->getDeposites();

        return $this->json($purchases);
    }

    /**
     * @Rest\Patch("/{id}")
     *
     *
     * @throws SmartcardActivationDeactivatedException
     * @throws SmartcardNotAllowedStateTransition
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function update(
        Smartcard $smartcard,
        UpdateSmartcardInputType $updateSmartcardInputType,
        SmartcardService $smartcardService
    ): JsonResponse {
        $user = $this->tokenStorage->getToken()->getUser();

        $role = $this->roleRepository->findOneBy([
            'code' => RoleType::ADMIN,
        ]);

        if ($role && $user->hasRole($role)) {
            $smartcard = $this->smartcardRepository->find($smartcard);
            $smartcard = $smartcardService->update($smartcard, $updateSmartcardInputType);

            return $this->json($smartcard);
        } else {
            throw new AccessDeniedException('You do not have the privilege to update the Smartcard');
        }
    }
}
