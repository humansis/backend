<?php

namespace Services;

use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Entity\AssistanceBeneficiary;
use Entity\Beneficiary;
use Entity\CountrySpecific;
use Enum\ReliefPackageState;
use InputType\Assistance\UpdateReliefPackageInputType;
use Enum\ModalityType;
use Exception\RemoveDistributionException;
use InputType\ResetReliefPackageInputType;
use Repository\AssistanceBeneficiaryRepository;
use Repository\BeneficiaryRepository;
use Repository\CountrySpecificRepository;
use Entity\Assistance;
use Doctrine\ORM\NonUniqueResultException;
use Entity\Assistance\ReliefPackage;
use InputType\Assistance\DistributeBeneficiaryReliefPackagesInputType;
use InputType\Assistance\DistributeReliefPackagesInputType;
use OutputType\Assistance\DistributeReliefPackagesOutputType;
use Repository\Assistance\ReliefPackageRepository;
use Repository\SmartcardBeneficiaryRepository;
use Repository\SmartcardDepositRepository;
use Throwable;
use Utils\DecimalNumber\DecimalNumberFactory;
use Workflow\ReliefPackageTransitions;
use Psr\Log\LoggerInterface;
use Symfony\Component\Workflow\Registry;
use Entity\User;

/**
 * Class AssistanceDistributionService
 *
 * @package NewBundle\Services
 */
class AssistanceDistributionService
{
    final public const COUNTRY_SPECIFIC_ID_NUMBER = 'Secondary ID Number';

    public function __construct(
        private readonly ReliefPackageRepository $reliefPackageRepository,
        private readonly BeneficiaryRepository $beneficiaryRepository,
        private readonly CountrySpecificRepository $countrySpecificRepository,
        private readonly LoggerInterface $logger,
        private readonly Registry $registry,
        private readonly EntityManagerInterface $em,
        private readonly SmartcardDepositRepository $smartcardDepositRepository,
        private readonly SmartcardBeneficiaryRepository $smartcardBeneficiaryRepository,
        private readonly AssistanceBeneficiaryRepository $assistanceBeneficiaryRepository
    ) {
    }

    /**
     * @param DistributeReliefPackagesInputType[] $packages
     *
     */
    public function distributeByReliefIds(array $packages, User $distributor): DistributeReliefPackagesOutputType
    {
        $distributeReliefPackageOutputType = new DistributeReliefPackagesOutputType();
        foreach ($packages as $packageUpdate) {
            try {
                /** @var ReliefPackage $reliefPackage */
                $reliefPackage = $this->reliefPackageRepository->find($packageUpdate->getId());
                $amountToDistribute = $packageUpdate->getAmountDistributed() ?? $reliefPackage->getCurrentUndistributedAmount();

                $result = $this->distributeSinglePackage(
                    $distributeReliefPackageOutputType,
                    $reliefPackage,
                    $amountToDistribute,
                    $reliefPackage->getCurrentUndistributedAmount(),
                    $distributor
                );
                $distributeReliefPackageOutputType = $result['output'];
            } catch (Throwable $ex) {
                $distributeReliefPackageOutputType->addFailed($packageUpdate->getId(), $ex->getMessage());
                $this->logger->error($ex->getMessage());
            }
        }

        return $distributeReliefPackageOutputType;
    }

    /**
     * @param DistributeBeneficiaryReliefPackagesInputType[] $inputPackages
     *
     * @throws NonUniqueResultException
     */
    public function distributeByBeneficiaryIdAndAssistanceId(
        array $inputPackages,
        Assistance $assistance,
        User $distributor
    ): DistributeReliefPackagesOutputType {
        $distributeReliefPackageOutputType = new DistributeReliefPackagesOutputType();
        $countrySpecific = $this->countrySpecificRepository->findOneBy([
            'fieldString' => self::COUNTRY_SPECIFIC_ID_NUMBER,
            'countryIso3' => $assistance->getProject()->getCountryIso3(),
        ]);
        foreach ($inputPackages as $packageData) {
            $distributeReliefPackageOutputType = $this->processPackageData(
                $packageData,
                $distributeReliefPackageOutputType,
                $assistance,
                $distributor,
                $countrySpecific
            );
        }

        return $distributeReliefPackageOutputType;
    }

    private function processPackageData(
        DistributeBeneficiaryReliefPackagesInputType $packageData,
        DistributeReliefPackagesOutputType $distributeReliefPackageOutputType,
        Assistance $assistance,
        User $distributor,
        ?CountrySpecific $countrySpecific
    ) {
        $beneficiaries = $this->beneficiaryRepository->findByIdentityAndAssistance(
            $packageData->getIdNumber(),
            $assistance,
            $countrySpecific
        );
        if ((is_countable($beneficiaries) ? count($beneficiaries) : 0) === 0) {
            return $distributeReliefPackageOutputType->addNotFound($packageData->getIdNumber());
        }
        if ((is_countable($beneficiaries) ? count($beneficiaries) : 0) > 1) {
            return $distributeReliefPackageOutputType->addConflictId($packageData->getIdNumber(), $beneficiaries);
        }
        if ((is_countable($beneficiaries) ? count($beneficiaries) : 0) === 1) {
            $beneficiary = array_shift($beneficiaries);
            /** @var ReliefPackage[] $packages */
            $packages = $this->reliefPackageRepository->findByAssistanceAndBeneficiary($assistance, $beneficiary);

            if ($packageData->getAmountDistributed() === null) {
                foreach ($packages as $reliefPackage) {
                    $result = $this->distributeSinglePackage(
                        $distributeReliefPackageOutputType,
                        $reliefPackage,
                        $reliefPackage->getCurrentUndistributedAmount(),
                        $reliefPackage->getCurrentUndistributedAmount(),
                        $distributor,
                        $beneficiary,
                        $packageData->getIdNumber()
                    );
                    $distributeReliefPackageOutputType = $result['output'];
                }
            } else {
                $totalSumToDistribute = $packageData->getAmountDistributed();
                foreach ($packages as $reliefPackage) {
                    $result = $this->distributeSinglePackage(
                        $distributeReliefPackageOutputType,
                        $reliefPackage,
                        $reliefPackage->getCurrentUndistributedAmount(),
                        $totalSumToDistribute,
                        $distributor,
                        $beneficiary,
                        $packageData->getIdNumber()
                    );
                    $totalSumToDistribute = $totalSumToDistribute - $result['amount'];
                    $distributeReliefPackageOutputType = $result['output'];
                }
            }
        }

        return $distributeReliefPackageOutputType;
    }

    private function distributeSinglePackage(
        DistributeReliefPackagesOutputType $distributeReliefPackageOutputType,
        ReliefPackage $reliefPackage,
        $targetDistributionAmount,
        $totalUndistributedAmount,
        User $distributor,
        Beneficiary $beneficiary = null,
        $idNumber = null
    ) {
        $beneficiaryId = $beneficiary?->getId();
        if ($reliefPackage->isFullyDistributed()) {
            $output = $distributeReliefPackageOutputType->addAlreadyDistributed(
                $reliefPackage->getId(),
                $beneficiaryId,
                $idNumber
            );

            return ['amount' => 0, 'output' => $output];
        }
        $amount = 0;
        try {
            $toDistribute = min($targetDistributionAmount, $totalUndistributedAmount);
            $reliefPackage->addDistributedAmount($toDistribute);
            $this->startReliefPackageDistributionWorkflow($reliefPackage, $distributor);
            $amount = $toDistribute;
            $reliefPackage->isFullyDistributed() ? $distributeReliefPackageOutputType->addSuccessfullyDistributed(
                $reliefPackage->getId(),
                $beneficiaryId,
                $idNumber
            ) : $distributeReliefPackageOutputType->addPartiallyDistributed(
                $reliefPackage->getId(),
                $beneficiaryId,
                $idNumber
            );
        } catch (Throwable $ex) {
            $distributeReliefPackageOutputType->addFailed($reliefPackage->getId(), $ex->getMessage());
            $this->logger->error($ex->getMessage());
        } finally {
            return ['amount' => $amount, 'output' => $distributeReliefPackageOutputType];
        }
    }

    private function startReliefPackageDistributionWorkflow(ReliefPackage $reliefPackage, User $distributor)
    {
        $reliefPackage->setDistributedBy($distributor);
        // Assistance statistic cache is invalidated by workflow transition
        // for partially distribution process of invalidation cache should be changed
        $reliefPackageWorkflow = $this->registry->get($reliefPackage);
        if ($reliefPackageWorkflow->can($reliefPackage, ReliefPackageTransitions::DISTRIBUTE)) {
            $reliefPackageWorkflow->apply($reliefPackage, ReliefPackageTransitions::DISTRIBUTE);
        }
        $this->reliefPackageRepository->save($reliefPackage);
    }

    /**
     * @throws RemoveDistributionException
     */
    private function checkDataBeforeDelete(
        AssistanceBeneficiary $assistanceBeneficiary,
        ResetReliefPackageInputType $inputType
    ): void {
        $smartcardBeneficiary = $this->smartcardBeneficiaryRepository->findBySerialNumberAndBeneficiaryId(
            $inputType->getSmartcardCode(),
            $inputType->getBeneficiaryId()
        );
        if (!$smartcardBeneficiary) {
            throw new RemoveDistributionException(
                "Beneficiary ({$inputType->getBeneficiaryId()}) does not have assigned Smartcard with code ({$inputType->getSmartcardCode()})"
            );
        }

        if ($assistanceBeneficiary->getReliefPackages()->count() > 1) {
            throw new RemoveDistributionException(
                "This beneficiary ({$inputType->getBeneficiaryId()}) has more than one ReliefPackage in the same assistance ({$inputType->getAssistanceId()})"
            );
        }

        $reliefPackage = $assistanceBeneficiary->getReliefPackages()[0];
        if ($reliefPackage->getSmartcardDeposits()->count() === 0) {
            throw new RemoveDistributionException(
                "This beneficiary ({$inputType->getBeneficiaryId()}) did not receive a deposit for assistance ({$inputType->getAssistanceId()})"
            );
        }

        if ($reliefPackage->getModalityType() != ModalityType::SMART_CARD) {
            throw new RemoveDistributionException("Only Relief Packages that use the smartcard modality are allowed");
        }

        if ($inputType->getDepositId()) {
            $smartcardDeposit = $this->smartcardDepositRepository->find($inputType->getDepositId());
            if ($smartcardDeposit->getReliefPackage()->getId() !== $reliefPackage->getId()) {
                throw new RemoveDistributionException(
                    "Deposit #{$inputType->getDepositId()} is connected with inconsistent Relief Package id #{$smartcardDeposit->getReliefPackage()->getId()}. Beneficiary #{$inputType->getBeneficiaryId()} has generated Relief Package #{$reliefPackage->getId()}."
                );
            }
        } else {
            if ($reliefPackage->getSmartcardDeposits()->count() > 1) {
                throw new RemoveDistributionException(
                    "Relief Package #{$reliefPackage->getId()} has more then 1 Deposit. Please specify Deposit id to remove in the request."
                );
            }
        }
    }

    /**
     * @throws RemoveDistributionException
     * @throws Exception
     */
    public function deleteDistribution(ResetReliefPackageInputType $inputType): void
    {
        $assistanceBeneficiary = $this->assistanceBeneficiaryRepository->findByAssistanceAndBeneficiary(
            $inputType->getAssistanceId(),
            $inputType->getBeneficiaryId()
        );

        if (!$assistanceBeneficiary) {
            throw new RemoveDistributionException(
                "This beneficiary ({$inputType->getBeneficiaryId()}) doesn't belong to this assistance ({$inputType->getAssistanceId()})"
            );
        }

        $this->checkDataBeforeDelete($assistanceBeneficiary, $inputType);

        $reliefPackage = $assistanceBeneficiary->getReliefPackages()[0];
        $this->em->getConnection()->beginTransaction();
        try {
            if ($inputType->getDepositId()) {
                $smartcardDeposit = $this->smartcardDepositRepository->find($inputType->getDepositId());
                $restOfDistributedAmount = (DecimalNumberFactory::create($reliefPackage->getAmountDistributed()))
                    ->minus(DecimalNumberFactory::create($smartcardDeposit->getValue()))
                    ->round(2);
            } else {
                $smartcardDeposit = $reliefPackage->getSmartcardDeposits()[0];
            }
            $this->em->remove($smartcardDeposit);
            if (isset($restOfDistributedAmount) && (float) $restOfDistributedAmount > 0) {
                $reliefPackage->setAmountDistributed($restOfDistributedAmount);
            } else {
                $reliefPackage->setAmountDistributed('0');
                $reliefPackage->setState(ReliefPackageState::TO_DISTRIBUTE);
                $reliefPackage->setDistributedAt(null);
                $reliefPackage->setDistributedBy(null);
            }
            if ($inputType->getNote()) {
                $reliefPackage->setNotes($inputType->getNote());
            }
            $this->em->persist($reliefPackage);
            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch (Throwable $ex) {
            if ($this->em->getConnection()->isTransactionActive()) {
                $this->em->getConnection()->rollBack();
            }
            $this->logger->error("Can not delete distribution: " . $ex->getMessage());
            throw new RemoveDistributionException("Can not delete distribution");
        }
    }

    /**
     * IMPORTANT WARNING: This function updates the status of the relief package directly
     * without checking transition states, and was used just in the support app.
     */
    public function update(ReliefPackage $reliefPackage, UpdateReliefPackageInputType $inputPackages): ReliefPackage
    {
        $reliefPackage->setState($inputPackages->getState());
        if ($inputPackages->getNotes() != null) {
            $reliefPackage->setNotes($inputPackages->getNotes());
        }

        if ($inputPackages->getAmountDistributed() != null) {
            $reliefPackage->setAmountDistributed($inputPackages->getAmountDistributed());
        }
        $reliefPackage->setLastModifiedNow();
        $this->reliefPackageRepository->save($reliefPackage);

        return $reliefPackage;
    }
}
