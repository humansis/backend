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
                if ($packageUpdate->getAmountDistributed() === null) {
                    $reliefPackage->distributeRest();
                } else {
                    $reliefPackage->addAmountOfDistributed($packageUpdate->getAmountDistributed());
                }
                if ($reliefPackage->getAmountToDistribute() === $reliefPackage->getAmountDistributed()) {
                    $distributeReliefPackageOutputType->addSuccessfullyDistributed($packageUpdate->getId());
                } else {
                    $distributeReliefPackageOutputType->addPartiallyDistributed($packageUpdate->getId());
                }
                $this->startReliefPackageDitributionWorkflow($reliefPackage, $distributor);
            } catch (\Throwable $ex) {
                $distributeReliefPackageOutputType->addFailed($packageUpdate->getId());
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
        foreach ($inputPackages as $packageUpdate) {
            $beneficiary = $this->beneficiaryRepository->find($packageUpdate->getBeneficiaryId());
            /** @var ReliefPackage[] $packages */
            $packages = $this->reliefPackageRepository->findByAssistanceAndBeneficiary($assistance, $beneficiary);

            if ($packageUpdate->getAmountDistributed() === null) {
                foreach ($packages as $reliefPackage) {
                    $result = $this->distributeSinglePackage(
                        $reliefPackage,
                        $reliefPackage->getCurrentUndistributedAmount(),
                        $reliefPackage->getCurrentUndistributedAmount(),
                        $distributor,
                        $distributeReliefPackageOutputType);
                    $distributeReliefPackageOutputType = $result['output'];
                }
            } else {
                $totalSumToDistribute = $packageUpdate->getAmountDistributed();
                foreach ($packages as $reliefPackage) {
                    $result = $this->distributeSinglePackage($reliefPackage,
                        $reliefPackage->getCurrentUndistributedAmount(),
                        $totalSumToDistribute,
                        $distributor,
                    $distributeReliefPackageOutputType);
                    $totalSumToDistribute = $totalSumToDistribute - $result['amount'];
                    $distributeReliefPackageOutputType = $result['output'];
                }
            }
        }
        return  $distributeReliefPackageOutputType;
    }

    private function distributeSinglePackage(ReliefPackage $reliefPackage, $targetDistributionAmount, $totalUndistributedAmount, User $distributor, DistributeReliefPackagesOutputType $distributeReliefPackageOutputType) {
        $amount = 0;
        try {
            $toDistribute = min($targetDistributionAmount, $totalUndistributedAmount);
            $reliefPackage->addAmountOfDistributed($toDistribute);
            $this->startReliefPackageDitributionWorkflow($reliefPackage, $distributor);
            $amount = $toDistribute;
            $reliefPackage->isFullyDistributed() ? $distributeReliefPackageOutputType->addSuccessfullyDistributed($reliefPackage->getId()) : $distributeReliefPackageOutputType->addPartiallyDistributed($reliefPackage->getId());
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
