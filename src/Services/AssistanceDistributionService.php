<?php

namespace Services;

use Entity\Beneficiary;
use Entity\CountrySpecific;
use Repository\BeneficiaryRepository;
use Repository\CountrySpecificRepository;
use Entity\Assistance;
use Doctrine\ORM\NonUniqueResultException;
use Entity\Assistance\ReliefPackage;
use InputType\Assistance\DistributeBeneficiaryReliefPackagesInputType;
use InputType\Assistance\DistributeReliefPackagesInputType;
use OutputType\Assistance\DistributeReliefPackagesOutputType;
use Repository\Assistance\ReliefPackageRepository;
use Throwable;
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
    public const COUNTRY_SPECIFIC_ID_NUMBER = 'Secondary ID Number';

    /**
     * @var ReliefPackageRepository
     */
    private $reliefPackageRepository;

    /**
     * @var BeneficiaryRepository
     */
    private $beneficiaryRepository;

    /**
     * @var CountrySpecificRepository
     */
    private $countrySpecificRepository;

    private $logger;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @param ReliefPackageRepository $reliefPackageRepository
     * @param BeneficiaryRepository $beneficiaryRepository
     * @param CountrySpecificRepository $countrySpecificRepository
     * @param Registry $registry
     */
    public function __construct(
        ReliefPackageRepository $reliefPackageRepository,
        BeneficiaryRepository $beneficiaryRepository,
        CountrySpecificRepository $countrySpecificRepository,
        LoggerInterface $logger,
        Registry $registry
    ) {
        $this->reliefPackageRepository = $reliefPackageRepository;
        $this->beneficiaryRepository = $beneficiaryRepository;
        $this->countrySpecificRepository = $countrySpecificRepository;
        $this->registry = $registry;
        $this->logger = $logger;
    }

    /**
     * @param DistributeReliefPackagesInputType[] $packages
     * @param User $distributor
     *
     * @return DistributeReliefPackagesOutputType
     */
    public function distributeByReliefIds(array $packages, User $distributor): DistributeReliefPackagesOutputType
    {
        $distributeReliefPackageOutputType = new DistributeReliefPackagesOutputType();
        foreach ($packages as $packageUpdate) {
            try {
                /** @var ReliefPackage $reliefPackage */
                $reliefPackage = $this->reliefPackageRepository->find($packageUpdate->getId());
                $amountToDistribute = $packageUpdate->getAmountDistributed(
                ) === null ? $reliefPackage->getCurrentUndistributedAmount() : $packageUpdate->getAmountDistributed();

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
     * @param Assistance $assistance
     * @param User $distributor
     *
     * @return DistributeReliefPackagesOutputType
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
        if (count($beneficiaries) === 0) {
            return $distributeReliefPackageOutputType->addNotFound($packageData->getIdNumber());
        }
        if (count($beneficiaries) > 1) {
            return $distributeReliefPackageOutputType->addConflictId($packageData->getIdNumber(), $beneficiaries);
        }
        if (count($beneficiaries) === 1) {
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
        $beneficiaryId = isset($beneficiary) ? $beneficiary->getId() : null;
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
}
