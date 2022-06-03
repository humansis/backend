<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Smartcard;

use CommonBundle\InputType\RequestConverter;
use Doctrine\ORM\EntityManager;
use NewApiBundle\Component\Smartcard\Deposit\DepositFactory;
use NewApiBundle\Entity\SynchronizationBatch\Deposits;
use NewApiBundle\InputType\Smartcard\DepositInputType;
use NewApiBundle\InputType\SynchronizationBatch\CreateDepositInputType;
use NewApiBundle\Repository\Assistance\ReliefPackageRepository;
use NewApiBundle\Workflow\ReliefPackageTransitions;
use NewApiBundle\Workflow\SynchronizationBatchTransitions;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Workflow\Registry;
use Symfony\Component\Workflow\TransitionBlocker;
use UserBundle\Entity\User;
use VoucherBundle\Utils\SmartcardService;

class SmartcardDepositService
{
    /** @var EntityManager */
    private $em;

    /** @var Registry $workflowRegistry */
    private $workflowRegistry;

    /** @var ValidatorInterface */
    private $validator;

    /**
     * @var DepositFactory
     */
    private $depositFactory;

    /**
     * @var ReliefPackageRepository
     */
    private $reliefPackageRepository;

    public function __construct(
        EntityManager           $em,
        Registry                $workflowRegistry,
        ValidatorInterface      $validator,
        DepositFactory          $depositFactory,
        ReliefPackageRepository $reliefPackageRepository
    )
    {
        $this->em = $em;
        $this->workflowRegistry = $workflowRegistry;
        $this->validator = $validator;
        $this->depositFactory = $depositFactory;
        $this->reliefPackageRepository = $reliefPackageRepository;
    }

    public function validateSync(Deposits $deposits): void
    {
        $workflow = $this->workflowRegistry->get($deposits);
        if (!$workflow->can($deposits, SynchronizationBatchTransitions::COMPLETE_VALIDATION)
            || !$workflow->can($deposits, SynchronizationBatchTransitions::FAIL_VALIDATION))
        {
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
                    $violation->add(new ConstraintViolation(
                        "ReliefPackage #{$depositInput->getReliefPackageId()} doesn't exits",
                        null,
                        [],
                        [],
                        'reliefPackageId',
                        $depositInput->getReliefPackageId()
                    ));
                } else {
                    $reliefPackageWorkflow = $this->workflowRegistry->get($reliefPackage);

                    if (!$reliefPackageWorkflow->can($reliefPackage, ReliefPackageTransitions::DISTRIBUTE)) {
                        $tb = $reliefPackageWorkflow->buildTransitionBlockerList($reliefPackage, ReliefPackageTransitions::DISTRIBUTE);;

                        $tbMessages = [];
                        /** @var TransitionBlocker $item */
                        foreach ($tb as $item) {
                            $tbMessages[] = $item->getMessage();
                        }

                        $violation->add(new ConstraintViolation(
                            "Relief package #{$depositInput->getReliefPackageId()} cannot be distributed. State of RP: '{$reliefPackage->getState()}'. Workflow blocker messages: [" . implode($tbMessages, ', ') . ']',
                            null,
                            [],
                            [],
                            'reliefPackageId',
                            $depositInput->getReliefPackageId()
                        ));
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

    private function deposit(CreateDepositInputType $input, User $user)
    {
        $reliefPackage = $this->reliefPackageRepository->find($input->getReliefPackageId());
        if (null == $reliefPackage) {
            throw new \InvalidArgumentException("ReliefPackage #{$input->getReliefPackageId()} doesn't exits");
        }

        $this->depositFactory->create(
            DepositInputType::createFromReliefPackage(
                $input->getSmartcardSerialNumber(),
                $reliefPackage->getId(),
                $reliefPackage->getAmountToDistribute(),
                $input->getBalanceAfter(),
                $input->getCreatedAt()
            ), $user
        );
    }

}
