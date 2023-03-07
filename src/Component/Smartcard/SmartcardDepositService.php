<?php

declare(strict_types=1);

namespace Component\Smartcard;

use Component\ReliefPackage\ReliefPackageService;
use Component\Smartcard\Exception\SmartcardDepositReliefPackageCanNotBeDistributedException;
use Doctrine\ORM\EntityNotFoundException;
use Entity\Assistance\ReliefPackage;
use Entity\AssistanceBeneficiary;
use Entity\SmartcardDeposit;
use Enum\ReliefPackageState;
use Component\Smartcard\Deposit\DepositFactory;
use Component\Smartcard\Deposit\Exception\DoubledDepositException;
use InputType\Smartcard\DepositInputType;
use Repository\Assistance\ReliefPackageRepository;
use Repository\UserRepository;
use Workflow\ReliefPackageTransitions;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Workflow\Registry;
use Symfony\Component\Workflow\TransitionBlocker;
use Repository\SmartcardDepositRepository;

class SmartcardDepositService
{
    public function __construct(
        private readonly Registry $workflowRegistry,
        private readonly DepositFactory $depositFactory,
        private readonly ReliefPackageRepository $reliefPackageRepository,
        private readonly SmartcardDepositRepository $smartcardDepositRepository,
        private readonly ReliefPackageService $reliefPackageService,
        private readonly UserRepository $userRepository,
    ) {
    }

    /**
     * @throws DoubledDepositException
     * @throws InvalidArgumentException
     * @throws EntityNotFoundException
     * @throws SmartcardDepositReliefPackageCanNotBeDistributedException
     */
    public function processDeposit(int $userId, string $smartcardNumber, DepositInputType $inputType): void
    {
        $user = $this->userRepository->getById($userId);
        $reliefPackage = $this->reliefPackageRepository->getById($inputType->getReliefPackageId());

        $inputType = $this->replaceMissingValueInDepositInputType($inputType, $reliefPackage);
        $this->validateReliefPackageDistribution($reliefPackage);

        $this->depositFactory->create(
            $smartcardNumber,
            $inputType,
            $user
        );
    }

    /**
     * @throws SmartcardDepositReliefPackageCanNotBeDistributedException
     */
    private function validateReliefPackageDistribution(ReliefPackage $reliefPackage): void
    {
        if (!$this->reliefPackageService->canBeDistributed($reliefPackage)) {
            $this->reliefPackageService->tryReuse($reliefPackage);
        }

        if (!$this->reliefPackageService->canBeDistributed($reliefPackage)) {
            $reliefPackageWorkflow = $this->workflowRegistry->get($reliefPackage);
            $transitionBlockerList = $reliefPackageWorkflow->buildTransitionBlockerList(
                $reliefPackage,
                ReliefPackageTransitions::DISTRIBUTE
            );

            throw new SmartcardDepositReliefPackageCanNotBeDistributedException(
                $reliefPackage,
                $transitionBlockerList
            );
        }
    }

    private function replaceMissingValueInDepositInputType(
        DepositInputType $inputType,
        ReliefPackage $reliefPackage
    ): DepositInputType {
        if ($inputType->getValue() === null) {
            $inputType->setValue($reliefPackage->getAmountToDistribute());
        }

        return $inputType;
    }

    /**
     * @param AssistanceBeneficiary[] $distributionBeneficiaries
     *
     * @return SmartcardDeposit[]
     */
    public function getDepositsForDistributionBeneficiaries(array $distributionBeneficiaries): array
    {
        $qb = $this->smartcardDepositRepository->createQueryBuilder('scd')
            ->select('scd, rp, ab')
            ->innerJoin('scd.reliefPackage', 'rp')
            ->innerJoin('rp.assistanceBeneficiary', 'ab')
            ->where('rp.state = :state')
            ->andWhere('ab.id IN (:abstractBeneficiaryIds)')
            ->setParameter('state', ReliefPackageState::DISTRIBUTED)
            ->setParameter(
                'abstractBeneficiaryIds',
                array_map(fn($distributionBeneficiary) => $distributionBeneficiary->getId(), $distributionBeneficiaries)
            );

        /** @var SmartcardDeposit[] $result */
        return $qb->getQuery()->getResult();
    }
}
