<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Smartcard\Deposit;

use DistributionBundle\Entity\AssistanceBeneficiary;
use DistributionBundle\Repository\AssistanceBeneficiaryRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\ORMException;
use Exception;
use NewApiBundle\Entity\Assistance\ReliefPackage;
use NewApiBundle\Enum\CacheTarget;
use NewApiBundle\Enum\ReliefPackageState;
use NewApiBundle\InputType\Smartcard\DepositInputType;
use NewApiBundle\Repository\Assistance\ReliefPackageRepository;
use NewApiBundle\Workflow\ReliefPackageTransitions;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Workflow\Registry;
use Symfony\Contracts\Cache\CacheInterface;
use UserBundle\Entity\User;
use VoucherBundle\Entity\Smartcard;
use VoucherBundle\Entity\SmartcardDeposit;
use VoucherBundle\Repository\SmartcardDepositRepository;
use VoucherBundle\Repository\SmartcardRepository;
use VoucherBundle\Utils\SmartcardService;

class Deposit
{

    /** @var SmartcardService */
    private $smartcardService;

    /** @var Registry $workflowRegistry */
    private $workflowRegistry;

    /**
     * @var AssistanceBeneficiaryRepository
     */
    private $assistanceBeneficiaryRepository;

    /**
     * @var ReliefPackageRepository
     */
    private $reliefPackageRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var TokenStorage
     */
    private $tokenStorage;

    /**
     * @var array
     */
    private $messages = [];

    /**
     * @var bool
     */
    private $suspicious = false;

    /**
     * @var ReliefPackage
     */
    private $reliefPackage;

    /**
     * @var AssistanceBeneficiary
     */
    private $assistanceBeneficiary;

    /**
     * @var DepositInputType
     */
    private $depositInputType;

    /**
     * @var Smartcard
     */
    private $smartcard;

    /**
     * @var SmartcardDeposit|null
     */
    private $deposit;

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
     * @var string
     */
    private $hash;

    /**
     * @var User|null
     */
    private $user;

    /**
     * @param SmartcardDepositRepository      $smartcardDepositRepository
     * @param SmartcardService                $smartcardService
     * @param SmartcardRepository             $smartcardRepository
     * @param Registry                        $workflowRegistry
     * @param AssistanceBeneficiaryRepository $assistanceBeneficiaryRepository
     * @param ReliefPackageRepository         $reliefPackageRepository
     * @param LoggerInterface                 $logger
     * @param TokenStorage                    $tokenStorage
     * @param CacheInterface                  $cache
     * @param DepositInputType                $depositInputType
     * @param User|null                       $user
     *
     * @throws NonUniqueResultException
     */
    public function __construct(
        SmartcardDepositRepository      $smartcardDepositRepository,
        SmartcardService                $smartcardService,
        SmartcardRepository             $smartcardRepository,
        Registry                        $workflowRegistry,
        AssistanceBeneficiaryRepository $assistanceBeneficiaryRepository,
        ReliefPackageRepository         $reliefPackageRepository,
        LoggerInterface                 $logger,
        TokenStorage                    $tokenStorage,
        CacheInterface                  $cache,
        DepositInputType                $depositInputType,
        ?User                           $user = null
    ) {
        $this->smartcardDepositRepository = $smartcardDepositRepository;
        $this->smartcardService = $smartcardService;
        $this->workflowRegistry = $workflowRegistry;
        $this->assistanceBeneficiaryRepository = $assistanceBeneficiaryRepository;
        $this->reliefPackageRepository = $reliefPackageRepository;
        $this->logger = $logger;
        $this->tokenStorage = $tokenStorage;
        $this->depositInputType = $depositInputType;
        $this->cache = $cache;
        $this->smartcardRepository = $smartcardRepository;

        $this->load();
        $this->user = $user ?? $this->tokenStorage->getToken()->getUser();
    }

    /**
     * @return SmartcardDeposit
     * @throws ORMException|InvalidArgumentException
     */
    public function createDeposit(): SmartcardDeposit
    {
        if ($this->deposit) {
            return $this->deposit;
        }

        $this->createNewDeposit();

        $reliefPackageWorkflow = $this->workflowRegistry->get($this->reliefPackage);
        if ($reliefPackageWorkflow->can($this->reliefPackage, ReliefPackageTransitions::DISTRIBUTE)) {
            $reliefPackageWorkflow->apply($this->reliefPackage, ReliefPackageTransitions::DISTRIBUTE);
        }

        $this->smartcardService->setMissingCurrency($this->smartcard, $this->reliefPackage);
        $this->smartcardService->setMissingCurrencyToPurchases($this->smartcard);
        $this->cache->delete(CacheTarget::assistanceId($this->assistanceBeneficiary->getAssistance()->getId()));
        $this->smartcardRepository->save($this->smartcard);

        return $this->deposit;
    }

    /**
     * @return void
     * @throws NonUniqueResultException
     * @throws Exception
     */
    private function load(): void
    {
        if ($this->depositInputType->getReliefPackageId()) {
            $this->loadReliefPackageFromInput();
            $this->assistanceBeneficiary = $this->reliefPackage->getAssistanceBeneficiary();
        } else {
            if ($this->depositInputType->getBeneficiaryId() && $this->depositInputType->getAssistanceId()) {
                $this->loadAssistanceBeneficiaryFromInput();
                $this->loadSuitableReliefPackageFromInput();
            } else {
                throw new Exception("Relief Package ID OR (Beneficiary AND Assistance ID must be set)");
            }
        }

        $this->generateHash();
        $this->loadSmartcard();
        $this->deposit = $this->smartcardDepositRepository->findByHash($this->hash);

        if (!$this->deposit) {
            $this->reliefPackage->addAmountOfDistributed($this->depositInputType->getValue());
            $this->reliefPackage->setDistributedBy($this->tokenStorage->getToken()->getUser());
            $this->checkReliefPackageWorkflow();
        }
    }

    private function createNewDeposit(): void
    {
        $this->deposit = SmartcardDeposit::create(
            $this->smartcard,
            $this->user,
            $this->reliefPackage,
            (float) $this->depositInputType->getValue(),
            null !== $this->depositInputType->getBalance() ? (float) $this->depositInputType->getBalance() : null,
            $this->depositInputType->getCreatedAt(),
            $this->suspicious,
            $this->messages
        );

        $this->smartcard->addDeposit($this->deposit);
    }

    private function loadReliefPackageFromInput(): void
    {
        $reliefPackage = $this->reliefPackageRepository->find($this->depositInputType->getReliefPackageId());
        if (null === $reliefPackage) {
            throw new NotFoundHttpException("Relief package #{$this->depositInputType->getReliefPackageId()} does not exist.");
        }

        $this->reliefPackage = $reliefPackage;
    }

    private function loadSmartcard(): void
    {
        $smartcard = $this->smartcardService->getActualSmartcard($this->depositInputType->getSerialNumber(),
            $this->reliefPackage->getAssistanceBeneficiary()->getBeneficiary(), $this->depositInputType->getCreatedAt());
        if (!$smartcard->getBeneficiary()) {
            $this->suspicious = true;
            $this->addMessage('Smartcard does not have assigned beneficiary.');
        }

        $this->smartcard = $smartcard;
    }

    /**
     * @return void
     * @throws NonUniqueResultException
     */
    private function loadSuitableReliefPackageFromInput(): void
    {
        // try to find relief package with correct state
        $reliefPackage = $this->reliefPackageRepository->findForSmartcardByAssistanceBeneficiary($this->assistanceBeneficiary,
            ReliefPackageState::TO_DISTRIBUTE);

        // try to find relief package with incorrect state but created before distribution date
        if (!$reliefPackage) {
            $reliefPackage = $this->reliefPackageRepository->findForSmartcardByAssistanceBeneficiary($this->assistanceBeneficiary, null,
                $this->depositInputType->getCreatedAt());
        }

        // try to find any relief package for distribution
        if (!$reliefPackage) {
            $reliefPackage = $this->reliefPackageRepository->findForSmartcardByAssistanceBeneficiary($this->assistanceBeneficiary);
        }

        if (!$reliefPackage) {
            $message = "Nothing to distribute for beneficiary #{$this->assistanceBeneficiary->getBeneficiary()->getId()} in assistance #{$this->assistanceBeneficiary->getAssistance()->getId()}";
            $this->logger->warning($message);
            throw new NotFoundHttpException($message);
        }

        $this->reliefPackage = $reliefPackage;
    }

    private function loadAssistanceBeneficiaryFromInput(): void
    {
        $assistanceBeneficiary = $this->assistanceBeneficiaryRepository->findByAssistanceAndBeneficiary($this->depositInputType->getAssistanceId(),
            $this->depositInputType->getBeneficiaryId());
        if (null == $assistanceBeneficiary) {
            throw new NotFoundHttpException("No beneficiary #$this->depositInputType->getBeneficiaryId() in assistance #{$this->depositInputType->getAssistanceId()}");
        }

        $this->assistanceBeneficiary = $assistanceBeneficiary;
    }

    private function checkReliefPackageWorkflow(): void
    {
        $reliefPackageWorkflow = $this->workflowRegistry->get($this->reliefPackage);

        if ($this->reliefPackage->getAmountDistributed() > $this->reliefPackage->getAmountToDistribute()) {
            $this->suspicious = true;
            $this->addMessage(sprintf('Relief package #%s amount of distributed (%s) is over to distribute (%s).', $this->reliefPackage->getId(),
                $this->reliefPackage->getAmountDistributed(), $this->reliefPackage->getAmountToDistribute()));
        }

        if (!$reliefPackageWorkflow->can($this->reliefPackage, ReliefPackageTransitions::DISTRIBUTE)) {
            $this->suspicious = true;
            $this->addMessage("Relief package #{$this->reliefPackage->getId()} is in invalid state ({$this->reliefPackage->getState()}).");
        }
    }

    private function addMessage(string $message)
    {
        $this->messages[] = $message;
    }

    private function generateHash(): void
    {
        $this->hash = md5($this->depositInputType->getSerialNumber().
            $this->depositInputType->getCreatedAt()->getTimestamp().
            $this->depositInputType->getValue().
            $this->reliefPackage->getUnit().
            $this->reliefPackage->getId()
        );
    }
}
