<?php declare(strict_types=1);

namespace NewApiBundle\Component\Smartcard\Deposit;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use NewApiBundle\Component\ReliefPackage\ReliefPackageService;
use NewApiBundle\Component\Smartcard\Deposit\Exception\DoubledDepositException;
use NewApiBundle\Component\Smartcard\SmartcardDepositService;
use NewApiBundle\Entity\Assistance\ReliefPackage;
use NewApiBundle\Enum\CacheTarget;
use NewApiBundle\InputType\Smartcard\DepositInputType;
use NewApiBundle\Repository\Assistance\ReliefPackageRepository;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\CacheInterface;
use UserBundle\Entity\User;
use VoucherBundle\Entity\Smartcard;
use VoucherBundle\Entity\SmartcardDeposit;
use VoucherBundle\Utils\SmartcardService;

class DepositFactory
{
    /**
     * @var SmartcardService
     */
    private $smartcardService;

    /**
     * @var ReliefPackageRepository
     */
    private $reliefPackageRepository;

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var array
     */
    private $messages = [];

    /**
     * @var bool
     */
    private $suspicious = false;

    /**
     * @var ReliefPackageService
     */
    private $reliefPackageService;

    /**
     * @var SmartcardDepositService
     */
    private $smartcardDepositService;

    public function __construct(
        SmartcardDepositService $smartcardDepositService,
        SmartcardService        $smartcardService,
        ReliefPackageRepository $reliefPackageRepository,
        CacheInterface          $cache,
        ReliefPackageService    $reliefPackageService
    ) {
        $this->smartcardService = $smartcardService;
        $this->reliefPackageRepository = $reliefPackageRepository;
        $this->cache = $cache;
        $this->reliefPackageService = $reliefPackageService;
        $this->smartcardDepositService = $smartcardDepositService;
    }

    /**
     * @param string           $smartcardSerialNumber
     * @param DepositInputType $depositInputType
     * @param User             $user
     *
     * @return SmartcardDeposit
     * @throws DoubledDepositException
     * @throws
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws InvalidArgumentException
     */
    public function create(string $smartcardSerialNumber, DepositInputType $depositInputType, User $user): SmartcardDeposit
    {
        $reliefPackage = $this->reliefPackageRepository->find($depositInputType->getReliefPackageId());
        $hash = $this->smartcardDepositService->generateDepositHash($smartcardSerialNumber, $depositInputType->getCreatedAt()->getTimestamp(),
            $depositInputType->getValue(),
            $reliefPackage);
        $this->smartcardDepositService->checkDepositDuplicity($hash);
        $smartcard = $this->smartcardService->getActualSmartcard($smartcardSerialNumber, $reliefPackage->getAssistanceBeneficiary()->getBeneficiary(),
            $depositInputType->getCreatedAt());
        $deposit = $this->createNewDepositRoot($smartcard, $user, $reliefPackage, $depositInputType, $hash);
        $this->reliefPackageService->addDeposit($reliefPackage, $deposit);
        $this->smartcardService->setMissingCurrency($smartcard, $reliefPackage);
        $this->cache->delete(CacheTarget::assistanceId($reliefPackage->getAssistanceBeneficiary()->getAssistance()->getId()));

        return $deposit;
    }

    /**
     * @param Smartcard        $smartcard
     * @param User             $user
     * @param ReliefPackage    $reliefPackage
     * @param DepositInputType $depositInputType
     * @param string           $hash
     *
     * @return SmartcardDeposit
     */
    private function createNewDepositRoot(
        Smartcard        $smartcard,
        User             $user,
        ReliefPackage    $reliefPackage,
        DepositInputType $depositInputType,
        string           $hash
    ): SmartcardDeposit {
        $deposit = SmartcardDeposit::create(
            $smartcard,
            $user,
            $reliefPackage,
            (float) $depositInputType->getValue(),
            null !== $depositInputType->getBalance() ? (float) $depositInputType->getBalance() : null,
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

}
