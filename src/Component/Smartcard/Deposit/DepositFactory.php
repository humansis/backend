<?php

declare(strict_types=1);

namespace Component\Smartcard\Deposit;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Component\ReliefPackage\ReliefPackageService;
use Component\Smartcard\Deposit\Exception\DoubledDepositException;
use Component\Smartcard\SmartcardDepositService;
use Entity\Assistance\ReliefPackage;
use Entity\User;
use Enum\CacheTarget;
use InputType\Smartcard\DepositInputType;
use Repository\Assistance\ReliefPackageRepository;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Entity\Smartcard;
use Entity\SmartcardDeposit;
use Repository\SmartcardDepositRepository;
use Utils\SmartcardService;

class DepositFactory
{
    private array $messages = [];

    private bool $suspicious = false;

    public function __construct(private readonly SmartcardDepositRepository $smartcardDepositRepository, private readonly SmartcardService $smartcardService, private readonly ReliefPackageRepository $reliefPackageRepository, private readonly CacheInterface $cache, private readonly ReliefPackageService $reliefPackageService, private readonly LoggerInterface $logger)
    {
    }

    /**
     *
     * @throws DoubledDepositException
     * @throws
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws InvalidArgumentException
     */
    public function create(
        string $smartcardSerialNumber,
        DepositInputType $depositInputType,
        User $user
    ): SmartcardDeposit {
        $reliefPackage = $this->reliefPackageRepository->find($depositInputType->getReliefPackageId());
        $hash = SmartcardDepositService::generateDepositHash(
            $smartcardSerialNumber,
            $depositInputType->getCreatedAt()->getTimestamp(),
            $depositInputType->getValue(),
            $reliefPackage
        );
        $this->checkDepositDuplicity($hash);
        $smartcard = $this->smartcardService->getActualSmartcardOrCreateNew(
            $smartcardSerialNumber,
            $reliefPackage->getAssistanceBeneficiary()->getBeneficiary(),
            $depositInputType->getCreatedAt()
        );
        $deposit = $this->createNewDepositRoot($smartcard, $user, $reliefPackage, $depositInputType, $hash);
        $this->reliefPackageService->addDeposit($reliefPackage, $deposit);
        $this->smartcardService->setMissingCurrencyToSmartcardAndPurchases($smartcard, $reliefPackage);
        $this->cache->delete(
            CacheTarget::assistanceId($reliefPackage->getAssistanceBeneficiary()->getAssistance()->getId())
        );

        return $deposit;
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
            $this->suspicious,
            $this->messages
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
