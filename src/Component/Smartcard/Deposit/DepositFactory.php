<?php

declare(strict_types=1);

namespace Component\Smartcard\Deposit;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
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
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Entity\Smartcard;
use Entity\SmartcardDeposit;
use Utils\DecimalNumber\DecimalNumberFactory;
use Utils\SmartcardService;

class DepositFactory
{
    public function __construct(
        private readonly SmartcardService $smartcardService,
        private readonly ReliefPackageRepository $reliefPackageRepository,
        private readonly CacheInterface $cache,
        private readonly ReliefPackageService $reliefPackageService,
        private readonly LoggerInterface $logger,
        private readonly UserRepository $userRepository,
        private readonly SerializerInterface $serializer,
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
        } catch (UniqueConstraintViolationException) {      // Warning, Entity manager is closed for this case
            $message = sprintf(
                "Deposit with same parameters already exists. Data: %s",
                json_encode(
                    array_merge(
                        ['userId' => $user->getId()],
                        $this->serializer->normalize($depositInputType),
                        ['smartcardSerialNumber' => $smartcardSerialNumber]
                    )
                )
            );
            $this->logger->info($message);
            throw new DoubledDepositException($message);
        }

        $this->addDepositToSmartcard($smartcard, $deposit);
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
        return new SmartcardDeposit(
            $smartcard,
            $user,
            $reliefPackage,
            (float) $depositInputType->getValue(),
            (float) $depositInputType->getBalance(),
            $depositInputType->getCreatedAt()
        );
    }

    private function addDepositToSmartcard(Smartcard $smartcard, SmartcardDeposit $smartcardDeposit): void
    {
        $smartcard->addDeposit($smartcardDeposit);
        if (!$smartcard->getBeneficiary()) {
            $smartcardDeposit->setSuspicious(true);
            $smartcardDeposit->addMessage('Smartcard does not have assigned beneficiary.');
        }
    }
}
