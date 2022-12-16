<?php

declare(strict_types=1);

namespace Component\Smartcard\Deposit;

use Component\ReliefPackage\ReliefPackageService;
use Component\Smartcard\Deposit\Exception\DoubledDepositException;
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
use Utils\DecimalNumber\DecimalNumberFactory;
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
     * @throws InvalidArgumentException
     */
    public function create(
        string $smartcardSerialNumber,
        DepositInputType $depositInputType,
        User $user,
        CreationContext | null $context = null,
    ): SmartcardDeposit {
        $reliefPackage = $this->reliefPackageRepository->find($depositInputType->getReliefPackageId());
        $smartcard = $this->smartcardService->getOrCreateActiveSmartcardForBeneficiary(
            $smartcardSerialNumber,
            $reliefPackage->getAssistanceBeneficiary()->getBeneficiary(),
            $depositInputType->getCreatedAt()
        );
        $deposit = $this->createNewDepositRoot($smartcard, $user, $reliefPackage, $depositInputType);

        try {
            $this->reliefPackageService->addDeposit($reliefPackage, $deposit, $context);
        } catch (UniqueConstraintViolationException) {
            // TODO log to table
            throw new DoubledDepositException($this->smartcardDepositRepository->findByHash($deposit->getHash()));
        }

        $this->smartcardService->setMissingCurrencyToSmartcardAndPurchases($smartcard, $reliefPackage);
        $this->cache->delete(
            CacheTarget::assistanceId($reliefPackage->getAssistanceBeneficiary()->getAssistance()->getId())
        );

        return $deposit;
    }

    /**
     * @throws DoubledDepositException
     * @throws InvalidArgumentException
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
            $value = (DecimalNumberFactory::create($reliefPackage->getAmountToDistribute()))
                ->minus(DecimalNumberFactory::create($reliefPackage->getAmountDistributed()))
                ->round(2);
        }

        return $this->create(
            $manualDistributionInputType->getSmartcardCode(),
            DepositInputType::create(
                $manualDistributionInputType->getReliefPackageId(),
                (float) $value,
                (float) $value,
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
    ): SmartcardDeposit {
        $deposit = new SmartcardDeposit(
            $smartcard,
            $user,
            $reliefPackage,
            (float) $depositInputType->getValue(),
            (float) $depositInputType->getBalance(),
            $depositInputType->getCreatedAt()
        );

        $smartcard->addDeposit($deposit);
        if (!$smartcard->getBeneficiary()) {
            $deposit->setSuspicious(true);
            $deposit->addMessage('Smartcard does not have assigned beneficiary.');
        }

        return $deposit;
    }
}
