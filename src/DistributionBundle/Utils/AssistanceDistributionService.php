<?php

namespace DistributionBundle\Utils;

use BeneficiaryBundle\Entity\AbstractBeneficiary;
use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Community;
use BeneficiaryBundle\Entity\Institution;
use BeneficiaryBundle\Exception\CsvParserException;
use BeneficiaryBundle\Repository\BeneficiaryRepository;
use CommonBundle\Entity\Location;
use CommonBundle\Pagination\Paginator;
use CommonBundle\Utils\LocationService;
use DateTime;
use DateTimeInterface;
use DistributionBundle\DTO\VulnerabilityScore;
use DistributionBundle\Entity\Assistance;
use DistributionBundle\Entity\AssistanceBeneficiary;
use DistributionBundle\Entity\ModalityType;
use DistributionBundle\Entity\SelectionCriteria;
use DistributionBundle\Enum\AssistanceTargetType;
use DistributionBundle\Enum\AssistanceType;
use DistributionBundle\Repository\AssistanceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\ORMException;
use Exception;
use NewApiBundle\Component\Assistance\AssistanceFactory;
use NewApiBundle\Component\SelectionCriteria\FieldDbTransformer;
use NewApiBundle\Entity\Assistance\ReliefPackage;
use NewApiBundle\Enum\CacheTarget;
use NewApiBundle\Enum\PersonGender;
use NewApiBundle\InputType\Assistance\DistributeBeneficiaryReliefPackagesInputType;
use NewApiBundle\InputType\Assistance\DistributeReliefPackagesInputType;
use NewApiBundle\InputType\AssistanceCreateInputType;
use NewApiBundle\OutputType\Assistance\DistributeReliefPackagesOutputType;
use NewApiBundle\Repository\Assistance\ReliefPackageRepository;
use NewApiBundle\Request\Pagination;
use NewApiBundle\Workflow\ReliefPackageTransitions;
use ProjectBundle\Entity\Project;
use Psr\Container\ContainerInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;
use Symfony\Component\Serializer\SerializerInterface as Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Workflow\Registry;
use Symfony\Contracts\Cache\CacheInterface;
use UserBundle\Entity\User;
use VoucherBundle\Entity\Voucher;
use function _PHPStan_8f2e45ccf\React\Promise\Stream\first;

/**
 * Class AssistanceDistributionService
 * @package DistributionBundle\Utils
 */
class AssistanceDistributionService
{

    /**
     * @var ReliefPackageRepository
     */
     private $reliefPackageRepository;

    /**
     * @var BeneficiaryRepository
     */
    private $beneficiaryRepository;

    /**
     * @var Registry
     */
     private $registry;

    /**
     * @param ReliefPackageRepository $reliefPackageRepository
     * @param BeneficiaryRepository   $beneficiaryRepository
     * @param Registry                $registry
     */
    public function __construct(ReliefPackageRepository $reliefPackageRepository, BeneficiaryRepository $beneficiaryRepository, Registry $registry)
    {
        $this->reliefPackageRepository = $reliefPackageRepository;
        $this->beneficiaryRepository = $beneficiaryRepository;
        $this->registry = $registry;
    }

    /**
     * @param DistributeReliefPackagesInputType[] $packages
     * @param User $distributor
     *
     * @return DistributeReliefPackagesOutputType
     */
    public function distributeByReliefIds(array $packages, User $distributor): DistributeReliefPackagesOutputType {
        $distributeReliefPackageOutputType = new DistributeReliefPackagesOutputType();
        foreach ($packages as $packageUpdate) {

            try {
                /** @var ReliefPackage $package */
                $reliefPackage = $this->reliefPackageRepository->find($packageUpdate->getId());
                $amountToDistribute = $packageUpdate->getAmountDistributed() === null ? $reliefPackage->getCurrentUndistributedAmount() : $packageUpdate->getAmountDistributed();

                $result = $this->distributeSinglePackage(
                    $distributeReliefPackageOutputType,
                    $reliefPackage,
                    $amountToDistribute,
                    $reliefPackage->getCurrentUndistributedAmount(),
                    $distributor
                );
                $distributeReliefPackageOutputType = $result['output'];
            } catch (\Throwable $ex) {
                $distributeReliefPackageOutputType->addFailed($packageUpdate->getId(), $ex->getMessage());
            }
        }
        return $distributeReliefPackageOutputType;
    }

    /**
     * @param DistributeBeneficiaryReliefPackagesInputType[]      $inputPackages
     * @param Assistance $assistance
     * @param User       $distributor
     *
     * @return DistributeReliefPackagesOutputType
     * @throws NonUniqueResultException
     */
    public function distributeByBeneficiaryIdAndAssistanceId(array $inputPackages, Assistance $assistance,User $distributor): DistributeReliefPackagesOutputType {
        $distributeReliefPackageOutputType = new DistributeReliefPackagesOutputType();
        foreach ($inputPackages as $packageData) {
            $distributeReliefPackageOutputType = $this->processPackageData($packageData, $distributeReliefPackageOutputType, $assistance, $distributor);
        }
        return  $distributeReliefPackageOutputType;
    }

    private function processPackageData(
        DistributeBeneficiaryReliefPackagesInputType $packageData,
        DistributeReliefPackagesOutputType $distributeReliefPackageOutputType,
        Assistance $assistance,
        User $distributor
    ) {
        $beneficiaries = $this->beneficiaryRepository->findByIdentityAndProject($packageData->getIdNumber(), $assistance->getProject());
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
                        $beneficiary
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
                        $beneficiary
                        );
                    $totalSumToDistribute = $totalSumToDistribute - $result['amount'];
                    $distributeReliefPackageOutputType = $result['output'];
                }
            }
        }
        return $distributeReliefPackageOutputType;
    }

    private function distributeSinglePackage( DistributeReliefPackagesOutputType $distributeReliefPackageOutputType, ReliefPackage $reliefPackage, $targetDistributionAmount, $totalUndistributedAmount, User $distributor, Beneficiary $beneficiary = NULL) {
        $beneficiaryId = isset($beneficiary) ? $beneficiary->getId() : NULL;
        if ($reliefPackage->isFullyDistributed()) {
            $output = $distributeReliefPackageOutputType->addAlreadyDistributed($reliefPackage->getId(), $beneficiaryId);
            return ['amount' => 0, 'output' => $output];
        }
        $amount = 0;
        try {
            $toDistribute = min($targetDistributionAmount, $totalUndistributedAmount);
            $reliefPackage->addAmountOfDistributed($toDistribute);
            $this->startReliefPackageDitributionWorkflow($reliefPackage, $distributor);
            $amount = $toDistribute;
            $reliefPackage->isFullyDistributed() ? $distributeReliefPackageOutputType->addSuccessfullyDistributed($reliefPackage->getId(), $beneficiaryId) : $distributeReliefPackageOutputType->addPartiallyDistributed($reliefPackage->getId(), $beneficiaryId);
        } catch (\Throwable $ex) {
            $distributeReliefPackageOutputType->addFailed($reliefPackage->getId());
        } finally {
            return ['amount' => $amount, 'output' => $distributeReliefPackageOutputType];
        }

    }

    private function startReliefPackageDitributionWorkflow(ReliefPackage $reliefPackage,User $distributor) {
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
