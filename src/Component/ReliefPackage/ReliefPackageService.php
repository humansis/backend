<?php

declare(strict_types=1);

namespace Component\ReliefPackage;

use Component\Smartcard\Deposit\CreationContext;
use DateTimeImmutable;
use Entity\Assistance\ReliefPackage;
use Enum\ReliefPackageState;
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

    public function addDeposit(
        ReliefPackage $reliefPackage,
        SmartcardDeposit $deposit,
        CreationContext | null $context = null
    ): void {
        $this->addDistributedAmount($reliefPackage, $deposit, $context?->getSpent());
        $this->checkDistributedAmount($reliefPackage, $deposit);
        $this->markReliefPackageAsDistributed($reliefPackage, $deposit, (bool) $context?->checkDistributionWorkflow());
        if ($context?->getNotes()) {
            $reliefPackage->setNotes($context->getNotes());
        }
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

    public function canBeDistributed(ReliefPackage $reliefPackage): bool
    {
        $reliefPackageWorkflow = $this->workflowRegistry->get($reliefPackage);

        return $reliefPackageWorkflow->can($reliefPackage, ReliefPackageTransitions::DISTRIBUTE);
    }

    public function tryReuse(ReliefPackage $reliefPackage): bool
    {
        $reliefPackageWorkflow = $this->workflowRegistry->get($reliefPackage);
        if ($reliefPackageWorkflow->can($reliefPackage, ReliefPackageTransitions::REUSE)) {
            $reliefPackageWorkflow->apply($reliefPackage, ReliefPackageTransitions::REUSE);

            return true;
        }

        return false;
    }

    private function addDistributedAmount(
        ReliefPackage $reliefPackage,
        SmartcardDeposit $deposit,
        string | null $spent = null
    ): void {
        $reliefPackage->addDistributedAmount($deposit->getValue());
        $reliefPackage->setDistributedBy($deposit->getDistributedBy());
        if ($spent) {
            $reliefPackage->addSpent($spent);
        }
    }

    private function markReliefPackageAsDistributed(
        ReliefPackage $reliefPackage,
        SmartcardDeposit $deposit,
        bool $checkWorkflow = true
    ): void {
        if ($checkWorkflow) {
            $reliefPackageWorkflow = $this->workflowRegistry->get($reliefPackage);
            if (!$reliefPackageWorkflow->can($reliefPackage, ReliefPackageTransitions::DISTRIBUTE)) {
                $deposit->setSuspicious(true);
                $message = "Relief package #{$reliefPackage->getId()} could not be set as Distributed because of invalid state ({$reliefPackage->getState()}).";
                $deposit->addMessage($message);
                $this->logger->info($message);
            } else {
                $this->applyReliefPackageTransition($reliefPackage, ReliefPackageTransitions::DISTRIBUTE);
            }
        } else {
            $reliefPackage->setState(ReliefPackageState::DISTRIBUTED);
            $reliefPackage->setDistributedAt(new DateTimeImmutable());
        }
    }

    private function checkDistributedAmount(ReliefPackage $reliefPackage, SmartcardDeposit $deposit): void
    {
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
    }
}
