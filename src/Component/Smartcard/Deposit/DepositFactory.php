<?php

declare(strict_types=1);

namespace Component\Smartcard\Deposit;

use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Component\ReliefPackage\ReliefPackageService;
use Component\Smartcard\Deposit\Exception\DoubledDepositException;
use Component\Smartcard\SmartcardDepositService;
use Entity\Assistance\ReliefPackage;
use Entity\User;
use Enum\CacheTarget;
use InputType\Smartcard\DepositInputType;
use InputType\Smartcard\ManualDistributionInputType;
use Repository\Assistance\ReliefPackageRepository;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Repository\UserRepository;
use Symfony\Contracts\Cache\CacheInterface;
use Entity\Smartcard;
use Entity\SmartcardDeposit;
use Repository\SmartcardDepositRepository;
use Utils\SmartcardService;

class DepositFactory
{
    public function __construct(
        private readonly SmartcardDepositRepository $smartcardDepositRepository,
        private readonly SmartcardService $smartcardService,
        private readonly ReliefPackageRepository $reliefPackageRepository,
        private readonly CacheInterface $cache,
        private readonly ReliefPackageService $reliefPackageService,
        private readonly LoggerInterface $logger,
        private readonly UserRepository $userRepository,
    ) {
    }

    /**
     * @throws DoubledDepositException
     * @throws OptimisticLockException
     * @throws InvalidArgumentException
     * @throws ORMException
     */
    public function create(
        string $smartcardSerialNumber,
        DepositInputType $depositInputType,
        User $user,
        CreationContext | null $context = null,
    ): SmartcardDeposit {
        $reliefPackage = $this->reliefPackageRepository->find($depositInputType->getReliefPackageId());
        $hash = SmartcardDepositService::generateDepositHash(
            $smartcardSerialNumber,
            $depositInputType->getCreatedAt()->getTimestamp(),
            $depositInputType->getValue(),
            $reliefPackage
        );
        $this->checkDepositDuplicity($hash);
        $smartcard = $this->smartcardService->getOrCreateActiveSmartcardForBeneficiary(
            $smartcardSerialNumber,
            $reliefPackage->getAssistanceBeneficiary()->getBeneficiary(),
            $depositInputType->getCreatedAt()
        );
        $deposit = $this->createNewDepositRoot($smartcard, $user, $reliefPackage, $depositInputType, $hash);
        $this->reliefPackageService->addDeposit($reliefPackage, $deposit, $context);
        $this->smartcardService->setMissingCurrencyToSmartcardAndPurchases($smartcard, $reliefPackage);
        $this->cache->delete(
            CacheTarget::assistanceId($reliefPackage->getAssistanceBeneficiary()->getAssistance()->getId())
        );

        return $deposit;
    }

    /**
     * @throws DoubledDepositException
     * @throws InvalidArgumentException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function createForSupportApp(
        ManualDistributionInputType $manualDistributionInputType,
    ): SmartcardDeposit {
        $context = new CreationContext(
            $manualDistributionInputType->isCheckState(),
            $manualDistributionInputType->getSpent(),
            $manualDistributionInputType->getNote()
        );
        if ($manualDistributionInputType->getValue()) {
            $value = $manualDistributionInputType->getValue();
        } else {
            $reliefPackage = $this->reliefPackageRepository->find($manualDistributionInputType->getReliefPackageId());
            $value = $reliefPackage->getAmountToDistribute() - $reliefPackage->getAmountDistributed();
        }

        return $this->create(
            $manualDistributionInputType->getSmartcardCode(),
            DepositInputType::create(
                $manualDistributionInputType->getReliefPackageId(),
                $value,
                $value,
                $manualDistributionInputType->getCreatedAt()
            ),
            $this->userRepository->find($manualDistributionInputType->getCreatedBy()),
            $context
        );
    }

    private function createNewDepositRoot(
        Smartcard $smartcard,
        User $user,
        ReliefPackage $reliefPackage,
        DepositInputType $depositInputType,
        string $hash
    ): SmartcardDeposit {
        $deposit = SmartcardDeposit::create(
            $smartcard,
            $user,
            $reliefPackage,
            (float) $depositInputType->getValue(),
            (float) $depositInputType->getBalance(),
            $depositInputType->getCreatedAt(),
            $hash,
        );

        $smartcard->addDeposit($deposit);
        if (!$smartcard->getBeneficiary()) {
            $deposit->setSuspicious(true);
            $deposit->addMessage('Smartcard does not have assigned beneficiary.');
        }

        return $deposit;
    }

    /**
     * @throws DoubledDepositException
     */
    private function checkDepositDuplicity(string $hash): void
    {
        $deposit = $this->smartcardDepositRepository->findByHash($hash);

        if ($deposit) {
            $this->logger->info(
                "Creation of deposit with hash {$deposit->getHash()} was omitted. It's already set in Deposit #{$deposit->getId()}"
            );
            throw new DoubledDepositException($deposit);
        }
    }
}
