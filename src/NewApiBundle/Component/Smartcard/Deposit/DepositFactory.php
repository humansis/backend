<?php declare(strict_types=1);

namespace NewApiBundle\Component\Smartcard\Deposit;

use DistributionBundle\Repository\AssistanceBeneficiaryRepository;
use Doctrine\ORM\NonUniqueResultException;
use NewApiBundle\InputType\Smartcard\DepositInputType;
use NewApiBundle\Repository\Assistance\ReliefPackageRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Workflow\Registry;
use Symfony\Contracts\Cache\CacheInterface;
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
     * @var DepositInputType
     */
    private $depositInputType;

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
        DepositInputType                $depositInputType
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
    }

    /**
     * @param DepositInputType $depositInputType
     *
     * @return Deposit
     * @throws NonUniqueResultException
     */
    public function create(DepositInputType $depositInputType): Deposit
    {
        return new Deposit($this->smartcardDepositRepository, $this->smartcardService, $this->smartcardRepository, $this->workflowRegistry,
            $this->assistanceBeneficiaryRepository, $this->reliefPackageRepository, $this->logger, $this->tokenStorage, $this->cache,
            $depositInputType);
    }
}
