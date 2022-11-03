<?php

declare(strict_types=1);

namespace Component\Smartcard;

use Entity\AssistanceBeneficiary;
use Entity\SmartcardDeposit;
use Entity\User;
use Enum\ReliefPackageState;
use InputType\RequestConverter;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Component\Smartcard\Deposit\DepositFactory;
use Component\Smartcard\Deposit\Exception\DoubledDepositException;
use Entity\Assistance\ReliefPackage;
use Entity\SynchronizationBatch\Deposits;
use InputType\Smartcard\DepositInputType;
use InputType\SynchronizationBatch\CreateDepositInputType;
use Repository\Assistance\ReliefPackageRepository;
use Workflow\ReliefPackageTransitions;
use Workflow\SynchronizationBatchTransitions;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Workflow\Registry;
use Symfony\Component\Workflow\TransitionBlocker;
use Repository\SmartcardDepositRepository;

class SmartcardDepositService
{
    public function __construct(private readonly EntityManager $em, private readonly Registry $workflowRegistry, private readonly ValidatorInterface $validator, private readonly DepositFactory $depositFactory, private readonly ReliefPackageRepository $reliefPackageRepository, private readonly LoggerInterface $logger, private readonly SmartcardDepositRepository $smartcardDepositRepository)
    {
    }

    /**
     * @param               $value
     *
     */
    public static function generateDepositHash(
        string $smartcardSerialNumber,
        int $timestamp,
        $value,
        ReliefPackage $reliefPackage
    ): string {
        return md5(
            $smartcardSerialNumber .
            '-' .
            $timestamp .
            '-' .
            $value .
            '-' .
            $reliefPackage->getUnit() .
            '-' .
            $reliefPackage->getId()
        );
    }

    /**
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws InvalidArgumentException
     */
    public function validateSync(Deposits $deposits): void
    {
        $workflow = $this->workflowRegistry->get($deposits);
        if (
            !$workflow->can($deposits, SynchronizationBatchTransitions::COMPLETE_VALIDATION)
            || !$workflow->can($deposits, SynchronizationBatchTransitions::FAIL_VALIDATION)
        ) {
            return;
        }
        $anyError = false;
        $violations = [];
        $inputs = [];
        foreach ($deposits->getRequestData() as $key => $depositData) {
            $depositInput = RequestConverter::normalizeInputType($depositData, CreateDepositInputType::class);
            $violation = $this->validator->validate($depositInput);

            if ($depositInput->getReliefPackageId()) {
                $reliefPackage = $this->reliefPackageRepository->find($depositInput->getReliefPackageId());
                if (null == $reliefPackage) {
                    $violation->add(
                        new ConstraintViolation(
                            "ReliefPackage #{$depositInput->getReliefPackageId()} doesn't exits",
                            null,
                            [],
                            [],
                            'reliefPackageId',
                            $depositInput->getReliefPackageId()
                        )
                    );
                } else {
                    $reliefPackageWorkflow = $this->workflowRegistry->get($reliefPackage);

                    if (!$reliefPackageWorkflow->can($reliefPackage, ReliefPackageTransitions::DISTRIBUTE)) {
                        $tb = $reliefPackageWorkflow->buildTransitionBlockerList(
                            $reliefPackage,
                            ReliefPackageTransitions::DISTRIBUTE
                        );

                        $tbMessages = [];
                        /** @var TransitionBlocker $item */
                        foreach ($tb as $item) {
                            $tbMessages[] = $item->getMessage();
                        }

                        $violation->add(
                            new ConstraintViolation(
                                "Relief package #{$depositInput->getReliefPackageId()} cannot be distributed. State of RP: '{$reliefPackage->getState()}'. Workflow blocker messages: [" . implode(
                                    $tbMessages,
                                    ', '
                                ) . ']',
                                null,
                                [],
                                [],
                                'reliefPackageId',
                                $depositInput->getReliefPackageId()
                            )
                        );
                    }
                }
            }

            if (count($violation) > 0) {
                $anyError = true;
                $violations[$key] = $violation;
            } else {
                $violations[$key] = null;
                $inputs[$key] = $depositInput;
            }
        }

        $deposits->setViolations($violations);
        if ($anyError) {
            $workflow->apply($deposits, SynchronizationBatchTransitions::FAIL_VALIDATION);
        } else {
            $workflow->apply($deposits, SynchronizationBatchTransitions::COMPLETE_VALIDATION);
        }
        $this->em->persist($deposits);
        $this->em->flush();

        foreach ($inputs as $input) {
            $this->deposit($input, $deposits->getCreatedBy());
        }
    }

    /**
     *
     * @return void
     * @throws InvalidArgumentException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function deposit(CreateDepositInputType $input, User $user)
    {
        $reliefPackage = $this->reliefPackageRepository->find($input->getReliefPackageId());
        if (null == $reliefPackage) {
            throw new \InvalidArgumentException("ReliefPackage #{$input->getReliefPackageId()} doesn't exits");
        }

        try {
            $this->depositFactory->create(
                $input->getSmartcardSerialNumber(),
                DepositInputType::create(
                    $reliefPackage->getId(),
                    $reliefPackage->getAmountToDistribute(),
                    $input->getBalanceAfter(),
                    $input->getCreatedAt()
                ),
                $user
            );
        } catch (DoubledDepositException $e) {
            $this->logger->info(
                "Creation of deposit with hash {$e->getDeposit()->getHash()} was omitted. It's already set in Deposit #{$e->getDeposit()->getId()}"
            );
        }
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