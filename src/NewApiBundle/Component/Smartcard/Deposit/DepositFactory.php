<?php declare(strict_types=1);

namespace NewApiBundle\Component\Smartcard\Deposit;

use DateTimeInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use NewApiBundle\Component\Smartcard\Deposit\Exception\DoubledDepositException;
use NewApiBundle\Entity\Assistance\ReliefPackage;
use NewApiBundle\Enum\CacheTarget;
use NewApiBundle\InputType\Smartcard\DepositInputType;
use NewApiBundle\Repository\Assistance\ReliefPackageRepository;
use NewApiBundle\Workflow\ReliefPackageTransitions;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Workflow\Registry;
use Symfony\Contracts\Cache\CacheInterface;
use UserBundle\Entity\User;
use VoucherBundle\Entity\Smartcard;
use VoucherBundle\Entity\SmartcardDeposit;
use VoucherBundle\Repository\SmartcardDepositRepository;
use VoucherBundle\Repository\SmartcardRepository;
use VoucherBundle\Utils\SmartcardService;

class DepositFactory
{
    /**
     * @var SmartcardService
     */
    private $smartcardService;

    /**
     * @var Registry
     */
    private $workflowRegistry;

    /**
     * @var ReliefPackageRepository
     */
    private $reliefPackageRepository;

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var SmartcardRepository
     */
    private $smartcardRepository;

    /**
     * @var SmartcardDepositRepository
     */
    private $smartcardDepositRepository;

    /**
     * @var array
     */
    private $messages = [];

    /**
     * @var bool
     */
    private $suspicious = false;

    public function __construct(
        SmartcardDepositRepository      $smartcardDepositRepository,
        SmartcardService                $smartcardService,
        SmartcardRepository             $smartcardRepository,
        Registry                        $workflowRegistry,
        ReliefPackageRepository         $reliefPackageRepository,
        CacheInterface                  $cache
    ) {
        $this->smartcardDepositRepository = $smartcardDepositRepository;
        $this->smartcardService = $smartcardService;
        $this->workflowRegistry = $workflowRegistry;
        $this->reliefPackageRepository = $reliefPackageRepository;
        $this->cache = $cache;
        $this->smartcardRepository = $smartcardRepository;
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
        $reliefPackage = $this->getReliefPackage($depositInputType->getReliefPackageId());
        $hash = $this->generateHash($smartcardSerialNumber, $depositInputType->getCreatedAt()->getTimestamp(),
            $depositInputType->getValue(),
            $reliefPackage);
        $smartcard = $this->getSmartcard($smartcardSerialNumber, $depositInputType->getCreatedAt(), $reliefPackage);
        $deposit = $this->smartcardDepositRepository->findByHash($hash);

        if ($deposit) {
            throw new DoubledDepositException($deposit);
        } else {
            $reliefPackage->addAmountOfDistributed($depositInputType->getValue());
            $reliefPackage->setDistributedBy($user);
            $this->checkReliefPackageWorkflow($reliefPackage);
        }

        $deposit = $this->createNewDepositRoot($smartcard, $user, $reliefPackage, $depositInputType);

        $reliefPackageWorkflow = $this->workflowRegistry->get($reliefPackage);
        if ($reliefPackageWorkflow->can($reliefPackage, ReliefPackageTransitions::DISTRIBUTE)) {
            $reliefPackageWorkflow->apply($reliefPackage, ReliefPackageTransitions::DISTRIBUTE);
        }

        $this->smartcardService->setMissingCurrency($smartcard, $reliefPackage);
        $this->smartcardService->setMissingCurrencyToPurchases($smartcard);
        $this->smartcardRepository->save($smartcard);
        $this->cache->delete(CacheTarget::assistanceId($reliefPackage->getAssistanceBeneficiary()->getAssistance()->getId()));

        return $deposit;
    }


    /**
     * @param Smartcard        $smartcard
     * @param User             $user
     * @param ReliefPackage    $reliefPackage
     * @param DepositInputType $depositInputType
     *
     * @return SmartcardDeposit
     */
    private function createNewDepositRoot(
        Smartcard        $smartcard,
        User             $user,
        ReliefPackage    $reliefPackage,
        DepositInputType $depositInputType
    ): SmartcardDeposit {
        $deposit = SmartcardDeposit::create(
            $smartcard,
            $user,
            $reliefPackage,
            (float) $depositInputType->getValue(),
            null !== $depositInputType->getBalance() ? (float) $depositInputType->getBalance() : null,
            $depositInputType->getCreatedAt(),
            $this->suspicious,
            $this->messages
        );
        $smartcard->addDeposit($deposit);

        return $deposit;
    }

    /**
     * @param int $reliefPackageId
     *
     * @return ReliefPackage
     */
    private function getReliefPackage(int $reliefPackageId): ReliefPackage
    {
        $reliefPackage = $this->reliefPackageRepository->find($reliefPackageId);
        if (null === $reliefPackage) {
            throw new NotFoundHttpException("Relief package #$reliefPackageId does not exist.");
        }

        return $reliefPackage;
    }

    /**
     * @param string            $serialNumber
     * @param DateTimeInterface $createdAt
     * @param ReliefPackage     $reliefPackage
     *
     * @return Smartcard
     */
    private function getSmartcard(string $serialNumber, DateTimeInterface $createdAt, ReliefPackage $reliefPackage): Smartcard
    {
        $smartcard = $this->smartcardService->getActualSmartcard($serialNumber, $reliefPackage->getAssistanceBeneficiary()->getBeneficiary(),
            $createdAt);
        if (!$smartcard->getBeneficiary()) {
            $this->suspicious = true;
            $this->addMessage('Smartcard does not have assigned beneficiary.');
        }

        return $smartcard;
    }

    /**
     * @param ReliefPackage $reliefPackage
     *
     * @return void
     */
    private function checkReliefPackageWorkflow(ReliefPackage $reliefPackage): void
    {
        $reliefPackageWorkflow = $this->workflowRegistry->get($reliefPackage);

        if ($reliefPackage->getAmountDistributed() > $reliefPackage->getAmountToDistribute()) {
            $this->suspicious = true;
            $this->addMessage(sprintf('Relief package #%s amount of distributed (%s) is over to distribute (%s).', $reliefPackage->getId(),
                $reliefPackage->getAmountDistributed(), $reliefPackage->getAmountToDistribute()));
        }

        if (!$reliefPackageWorkflow->can($reliefPackage, ReliefPackageTransitions::DISTRIBUTE)) {
            $this->suspicious = true;
            $this->addMessage("Relief package #{$reliefPackage->getId()} is in invalid state ({$reliefPackage->getState()}).");
        }
    }

    /**
     * @param string $message
     *
     * @return void
     */
    private function addMessage(string $message): void
    {
        $this->messages[] = $message;
    }

    /**
     * @param string        $serialNumber
     * @param int           $timestamp
     * @param               $value
     * @param ReliefPackage $reliefPackage
     *
     * @return string
     */
    private function generateHash(string $serialNumber, int $timestamp, $value, ReliefPackage $reliefPackage): string
    {
        return md5($serialNumber.
            $timestamp.
            $value.
            $reliefPackage->getUnit().
            $reliefPackage->getId()
        );
    }
}
