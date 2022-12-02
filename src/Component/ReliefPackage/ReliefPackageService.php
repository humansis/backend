<?php

declare(strict_types=1);

namespace Component\ReliefPackage;

use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Entity\Assistance\ReliefPackage;
use LogicException;
use Workflow\ReliefPackageTransitions;
use Psr\Log\LoggerInterface;
use Symfony\Component\Workflow\Registry;
use Entity\SmartcardDeposit;
use Repository\SmartcardDepositRepository;

class ReliefPackageService
{
    public function __construct(
        private readonly Registry $workflowRegistry,
        private readonly LoggerInterface $logger,
        private readonly SmartcardDepositRepository $smartcardDepositRepository
    ) {
    }

    /**
     *
     * @param ReliefPackage $reliefPackage
     * @param SmartcardDeposit $deposit
     * @return void
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function addDeposit(ReliefPackage $reliefPackage, SmartcardDeposit $deposit): void
    {
        $this->addDistributedAmount($reliefPackage, $deposit);
        $this->applyReliefPackageTransition($reliefPackage, ReliefPackageTransitions::DISTRIBUTE);
        $this->smartcardDepositRepository->save($deposit);
    }

    public function applyReliefPackageTransition(ReliefPackage $reliefPackage, string $transition): void
    {
        if (!in_array($transition, ReliefPackageTransitions::getAll())) {
            throw new LogicException(
                sprintf(
                    'Transition %s is not defined in Relief Package transitions list. Allowed transitions are (%s).',
                    $transition,
                    implode(',', ReliefPackageTransitions::getAll())
                )
            );
        }

        $reliefPackageWorkflow = $this->workflowRegistry->get($reliefPackage);
        if ($reliefPackageWorkflow->can($reliefPackage, $transition)) {
            $reliefPackageWorkflow->apply($reliefPackage, $transition);
        }
    }

    private function addDistributedAmount(ReliefPackage $reliefPackage, SmartcardDeposit $deposit): void
    {
        $reliefPackage->addDistributedAmount($deposit->getValue());
        $reliefPackage->setDistributedBy($deposit->getDistributedBy());

        if ($reliefPackage->getAmountDistributed() > $reliefPackage->getAmountToDistribute()) {
            $deposit->setSuspicious(true);
            $message = sprintf(
                'Deposit amount (%s) is over the total Relief Package (#%s) amount to distribute (%s).',
                $deposit->getValue(),
                $reliefPackage->getId(),
                $reliefPackage->getAmountToDistribute()
            );
            $deposit->addMessage($message);
            $this->logger->info($message);
        }

        $reliefPackageWorkflow = $this->workflowRegistry->get($reliefPackage);
        if (!$reliefPackageWorkflow->can($reliefPackage, ReliefPackageTransitions::DISTRIBUTE)) {
            $deposit->setSuspicious(true);
            $message = "Relief package #{$reliefPackage->getId()} could not be set as Distributed because of invalid state ({$reliefPackage->getState()}).";
            $deposit->addMessage($message);
            $this->logger->info($message);
        }
    }
}
