<?php

namespace Controller\SupportApp\Smartcard;

use Component\Smartcard\Exception\SmartcardActivationDeactivatedException;
use Component\Smartcard\Exception\SmartcardNotAllowedStateTransition;
use Controller\AbstractController;
use Entity\SmartcardBeneficiary;
use Enum\RoleType;
use FOS\RestBundle\Controller\Annotations as Rest;
use InputType\Smartcard\UpdateSmartcardInputType;
use Repository\RoleRepository;
use Repository\SmartcardBeneficiaryRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Utils\SmartcardService;

#[Rest\Route('/support-app/v1/smartcards')]
class SmartcardController extends AbstractController
{
    public function __construct(
        private readonly SmartcardService $smartcardService,
        private readonly SmartcardBeneficiaryRepository $smartcardBeneficiaryRepository,
        private readonly RoleRepository $roleRepository,
        private readonly TokenStorageInterface $tokenStorage
    ) {
    }

    #[Rest\Get('/{smartcardCode}')]
    public function smartcard(string $smartcardCode): JsonResponse
    {
        $smartcardBeneficiaries = $this->smartcardBeneficiaryRepository->findBy(['serialNumber' => $smartcardCode]);

        return $this->json(['data' => $smartcardBeneficiaries]);
    }

    #[Rest\Get('/{smartcardCode}/purchases')]
    public function smartcardPurchases(string $smartcardCode): JsonResponse
    {
        $smartcardBeneficiary = $this->smartcardService->getSmartcardByCode($smartcardCode);
        $purchases = $smartcardBeneficiary->getPurchases();

        return $this->json($purchases);
    }

    #[Rest\Get('/{smartcardCode}/deposits')]
    public function smartcardDeposits(string $smartcardCode): JsonResponse
    {
        $smartcardBeneficiary = $this->smartcardService->getSmartcardByCode($smartcardCode);
        $purchases = $smartcardBeneficiary->getDeposites();

        return $this->json($purchases);
    }

    /**
     * @throws SmartcardActivationDeactivatedException
     * @throws SmartcardNotAllowedStateTransition
     */
    #[Rest\Patch('/{id}')]
    public function update(
        SmartcardBeneficiary $smartcardBeneficiary,
        UpdateSmartcardInputType $updateSmartcardInputType,
        SmartcardService $smartcardService
    ): JsonResponse {
        $user = $this->tokenStorage->getToken()->getUser();

        $role = $this->roleRepository->findOneBy([
            'code' => RoleType::ADMIN,
        ]);

        if ($role && $user->hasRole($role)) {
            $smartcardBeneficiary = $this->smartcardBeneficiaryRepository->find($smartcardBeneficiary);
            $smartcardBeneficiary = $smartcardService->update($smartcardBeneficiary, $updateSmartcardInputType);

            return $this->json($smartcardBeneficiary);
        } else {
            throw new AccessDeniedException('You do not have the privilege to update the Smartcard');
        }
    }
}
