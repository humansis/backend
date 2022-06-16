<?php

namespace DistributionBundle\Utils;

use BeneficiaryBundle\Entity\AbstractBeneficiary;
use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Community;
use BeneficiaryBundle\Entity\Institution;
use BeneficiaryBundle\Exception\CsvParserException;
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
     * @var Registry
     */
     private $registry;

    /**
     * @param ReliefPackageRepository $repository
     */
    public function __construct(ReliefPackageRepository $reliefPackageRepository, Registry $registry)
    {
        $this->reliefPackageRepository = $reliefPackageRepository;
        $this->registry = $registry;
    }

    /**
     * @param DistributeReliefPackagesInputType[] $packages
     * @param User $distributor
     *
     * @return void
     */
    public function distributeByReliefIds(array $packages, User $distributor) {
        foreach ($packages as $packageUpdate) {
            /** @var ReliefPackage $package */
            $package = $this->reliefPackageRepository->find($packageUpdate->getId());
            if ($packageUpdate->getAmountDistributed() === null) {
                $package->distributeRest();
            } else {
                $package->addAmountOfDistributed($packageUpdate->getAmountDistributed());
            }
            $package->setDistributedBy($distributor);
            // Assistance statistic cache is invalidated by workflow transition
            // for partially distribution process of invalidation cache should be changed
            $reliefPackageWorkflow = $this->registry->get($package);
            if ($reliefPackageWorkflow->can($package, ReliefPackageTransitions::DISTRIBUTE)) {
                $reliefPackageWorkflow->apply($package, ReliefPackageTransitions::DISTRIBUTE);
            }
            $this->reliefPackageRepository->save($package);
        }
    }

    /**
     * @param DistributeBeneficiaryReliefPackagesInputType[] $packages
     *
     * @return void
     */
    public function distributeByBeneficiryIdAndAssistanceId(array $packages, $assistanceId,User $distributor) {
        foreach ($packages as $packageUpdate) {
            /** @var ReliefPackage $package */
            $package = $this->reliefPackageRepository->findByAssistanceAndBeneficiary($assistanceId, $packageUpdate->getBeneficiaryId());
            if ($packageUpdate->getAmountDistributed() === null) {
                $package->distributeRest();
            } else {
                $package->addAmountOfDistributed($packageUpdate->getAmountDistributed());
            }
            $package->setDistributedBy($distributor);
            // Assistance statistic cache is invalidated by workflow transition
            // for partially distribution process of invalidation cache should be changed
            $reliefPackageWorkflow = $this->registry->get($package);
            if ($reliefPackageWorkflow->can($package, ReliefPackageTransitions::DISTRIBUTE)) {
                $reliefPackageWorkflow->apply($package, ReliefPackageTransitions::DISTRIBUTE);
            }
            $this->reliefPackageRepository->save($package);
        }
    }

}
