<?php declare(strict_types=1);

namespace NewApiBundle\Component\Assistance\Domain;

use BeneficiaryBundle\Entity\AbstractBeneficiary;
use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Household;
use DistributionBundle\Entity;
use DistributionBundle\Entity\AssistanceBeneficiary;
use DistributionBundle\Enum\AssistanceTargetType;
use DistributionBundle\Repository\AssistanceBeneficiaryRepository;
use DistributionBundle\Repository\ModalityTypeRepository;
use DistributionBundle\Utils\Exception\RemoveBeneficiaryWithReliefException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\NoResultException;
use NewApiBundle\Component\Assistance\CommodityAssignBuilder;
use NewApiBundle\Component\Assistance\DTO\CommoditySummary;
use NewApiBundle\Component\Assistance\Enum\CommodityDivision;
use NewApiBundle\Entity\Assistance\ReliefPackage;
use NewApiBundle\Enum\CacheTarget;
use NewApiBundle\InputType\Assistance\CommodityInputType;
use NewApiBundle\Repository\AssistanceStatisticsRepository;
use NewApiBundle\Workflow\ReliefPackageTransitions;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Workflow\Registry;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class Assistance
{
    /** @var Entity\Assistance */
    private $assistanceRoot;
    /** @var CacheInterface */
    private $cache;
    /** @var ModalityTypeRepository */
    private $modalityTypeRepository;
    /** @var AssistanceStatisticsRepository */
    private $assistanceStatisticRepository;
    /** @var AssistanceBeneficiaryRepository */
    private $targetRepository;
    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var Registry $workflowRegistry */
    private $workflowRegistry;

    /**
     * @param Entity\Assistance               $assistanceEntity
     * @param CacheInterface                  $cache
     * @param ModalityTypeRepository          $modalityTypeRepository
     * @param AssistanceStatisticsRepository  $assistanceStatisticRepository
     * @param EntityManagerInterface          $entityManager
     * @param Registry                        $workflowRegistry
     * @param AssistanceBeneficiaryRepository $targetRepository
     */
    public function __construct(
        Entity\Assistance                                              $assistanceEntity,
        CacheInterface                                                 $cache,
        ModalityTypeRepository                                         $modalityTypeRepository,
        AssistanceStatisticsRepository                                 $assistanceStatisticRepository,
        EntityManagerInterface                                         $entityManager,
        Registry                                                       $workflowRegistry,
        AssistanceBeneficiaryRepository $targetRepository
    ) {
        $this->assistanceRoot = $assistanceEntity;
        $this->cache = $cache;
        $this->modalityTypeRepository = $modalityTypeRepository;
        $this->assistanceStatisticRepository = $assistanceStatisticRepository;
        $this->entityManager = $entityManager;
        $this->workflowRegistry = $workflowRegistry;
        $this->targetRepository = $targetRepository;
    }

    public function getStatistics(?string $countryIso3 = null): array
    {
        $key = CacheTarget::assistanceId($this->assistanceRoot->getId() ?? 'new');

        return $this->cache->get($key, function (ItemInterface $item) use ($countryIso3) {
            try{
                $statistics = $this->assistanceStatisticRepository->findByAssistance($this->assistanceRoot, $countryIso3);
            } catch (NoResultException $noResultException) {
                throw new NotFoundHttpException("Assistance {$this->assistanceRoot->getId()} is not in country $countryIso3");
            }

            // TODO probably better way could be normalize (or store whole) dto
            return [
                'id' => $statistics->getId(),
                'numberOfBeneficiaries' => $statistics->getNumberOfBeneficiaries(),
                'amountTotal' => $statistics->getAmountTotal(),
                'amountDistributed' => $statistics->getAmountDistributed(),
                'amountUsed' => $statistics->getAmountUsed(),
                'amountSent' => $statistics->getAmountSent(),
                'amountPickedUp' => $statistics->getAmountPickedUp(),
            ];
        });
    }

    public function validate(): self
    {
        $this->cleanCache();
        $this->assistanceRoot->setValidated(true);
        $this->assistanceRoot->setUpdatedOn(new \DateTimeImmutable());
        $this->recountReliefPackages();

        return $this;
    }

    public function unvalidate(): self
    {
        if (!$this->assistanceRoot->getValidated()) {
            throw new \InvalidArgumentException('Unable to unvalidate the assistance. Assistance wasn\'t validated.');
        }
        $this->cleanCache();

        $statistics = $this->getStatistics();

        if ($statistics['amountDistributed'] > 0) {
            throw new \InvalidArgumentException('Unable to unvalidate the assistance. Assistance is already started.');
        }
        $this->assistanceRoot->setValidated(false);
        $this->assistanceRoot->setUpdatedOn(new \DateTimeImmutable());

        return $this;
    }

    public function complete(): self
    {
        // TODO: checks
        $this->assistanceRoot->setCompleted();
        $this->expireUnusedReliefPackages();
        $this->cleanCache();

        return $this;
    }

    public function archive(): self
    {
        $this->assistanceRoot->setArchived(true);
        $this->cleanCache();
        $this->cancelUnusedReliefPackages();

        return $this;
    }

    public function addCommodity(CommodityInputType $commodityInputType): self
    {
        if ($this->assistanceRoot->getValidated()) {
            throw new \LogicException('Validated assistance shouldn\'t be edited');
        }
        $modalityType = $this->modalityTypeRepository->findOneBy(['name' => $commodityInputType->getModalityType()]);
        if (!$modalityType) {
            throw new EntityNotFoundException(sprintf('ModalityType %s does not exists', $commodityInputType->getModalityType()));
        }
        $commodity = new Entity\Commodity();
        $commodity->setModalityType($modalityType);
        $commodity->setDescription($commodityInputType->getDescription());
        $commodity->setValue($commodityInputType->getValue());
        $commodity->setUnit($commodityInputType->getUnit());
        $commodity->setDivision($commodityInputType->getDivision());
        $this->assistanceRoot->addCommodity($commodity);
        $this->recountReliefPackages();

        return $this;
    }

    public function hasDistributionStarted(): bool
    {
        $statistics = $this->getStatistics();

        return $statistics['amountDistributed'] > 0;
    }

    /**
     * @param array|null $targets who should be recounted, null => all targets in assistance
     */
    private function recountReliefPackages(?array $targets = null): void
    {
        $modalityUnits = [];
        $commodityBuilder = new CommodityAssignBuilder();
        foreach ($this->assistanceRoot->getCommodities() as $commodity) {
            $modality = $commodity->getModalityType()->getName();
            $unit = $commodity->getUnit();

            if (!isset($modalityUnits[$modality])) {
                $modalityUnits[$modality] = [];
            }
            if (!in_array($unit, $modalityUnits[$commodity->getModalityType()->getName()])) {
                $modalityUnits[$commodity->getModalityType()->getName()][] = $commodity->getUnit();
            }
            if ($commodity->getDivision() !== null) {
                if ($this->assistanceRoot->getTargetType() !== AssistanceTargetType::HOUSEHOLD) {
                    throw new \LogicException(sprintf("'%s' division is meaningful only for %s assistance, not for %s.",
                        CommodityDivision::PER_HOUSEHOLD,
                        AssistanceTargetType::HOUSEHOLD,
                        $this->assistanceRoot->getTargetType()
                    ));
                }
            }
            switch ($commodity->getDivision()) {
                case CommodityDivision::PER_HOUSEHOLD_MEMBER:
                    $commodityBuilder->addCommodityCallback($modality, $unit, function (AssistanceBeneficiary $target) use ($commodity) {
                        /** @var Household $household */
                        $household = $target->getBeneficiary();

                        // fallback for HH assistances directed to HHHs
                        if ($household instanceof Beneficiary) {
                            $household = $household->getHousehold();
                        }
                        return $commodity->getValue() * count($household->getBeneficiaries());
                    });
                    break;
                case CommodityDivision::PER_HOUSEHOLD:
                default:
                    $commodityBuilder->addCommodityValue($modality, $unit, $commodity->getValue());
                    break;
            }
        }


        foreach ($modalityUnits as $modalityName => $units) {
            foreach ($units as $unit) {
                foreach ($targets ?? $this->getTargets() as $target) {
                    $target->setCommodityToDistribute(
                        $modalityName,
                        $unit,
                        $commodityBuilder->getValue($target, $modalityName, $unit)
                    );
                }
            }
        }
    }

    /**
     * @param array|null $targets
     */
    private function expireUnusedReliefPackages(?array $targets = null): void
    {
        /** @var AssistanceBeneficiary $assistanceBeneficiary */
        foreach ($targets ?? $this->getTargets() as $assistanceBeneficiary) {
            /** @var ReliefPackage $reliefPackage */
            foreach ($assistanceBeneficiary->getReliefPackages() as $reliefPackage) {

                $reliefPackageWorkflow = $this->workflowRegistry->get($reliefPackage);

                if ($reliefPackageWorkflow->can($reliefPackage, ReliefPackageTransitions::EXPIRE)) {
                    $reliefPackageWorkflow->apply($reliefPackage, ReliefPackageTransitions::EXPIRE);
                }
            }
        }
    }

    public function getAssistanceRoot(): Entity\Assistance
    {
        return $this->assistanceRoot;
    }

    /**
     * @return AssistanceBeneficiary[]
     */
    public function getTargets(): iterable
    {
        return $this->assistanceRoot->getDistributionBeneficiaries();
    }

    private function cancelUnusedReliefPackages(?array $targets = null): void
    {
        /** @var AssistanceBeneficiary $assistanceBeneficiary */
        foreach ($targets ?? $this->getTargets() as $assistanceBeneficiary) {
            /** @var ReliefPackage $reliefPackage */
            foreach ($assistanceBeneficiary->getReliefPackages() as $reliefPackage) {

                $reliefPackageWorkflow = $this->workflowRegistry->get($reliefPackage);

                if ($reliefPackageWorkflow->can($reliefPackage, ReliefPackageTransitions::EXPIRE)) {
                    $reliefPackageWorkflow->apply($reliefPackage, ReliefPackageTransitions::EXPIRE);
                }
            }
        }
    }

    /**
     * @param AbstractBeneficiary $beneficiary
     * @param string|null         $justification
     * @param array|null          $vulnerabilityScore TODO: replace by class or serializable interface
     *
     * @return Assistance
     */
    public function addBeneficiary(AbstractBeneficiary $beneficiary, ?string $justification = null, ?array $vulnerabilityScore = null): self
    {
        $target = $this->targetRepository->findOneBy(['beneficiary' => $beneficiary, 'assistance' => $this->assistanceRoot]);
        if (null === $target) {
            $target = (new AssistanceBeneficiary())
                ->setAssistance($this->assistanceRoot)
                ->setBeneficiary($beneficiary)
                ->setRemoved(false);
            $this->assistanceRoot->addAssistanceBeneficiary($target);
            if (!empty($vulnerabilityScore)) {
                $target->setVulnerabilityScores(json_encode($vulnerabilityScore));
            }
        } else {
            $target->setRemoved(false);
        }

        if (!empty($justification)) {
            $target->setJustification($justification);
        }
        $this->recountReliefPackages([$target]);
        $this->assistanceRoot->setUpdatedOn(new \DateTime());
        $this->cleanCache();

        return $this;
    }

    /**
     * @param AbstractBeneficiary $beneficiary
     * @param string         $justification
     *
     * @return $this
     */
    public function removeBeneficiary(AbstractBeneficiary $beneficiary, string $justification): self
    {
        $target = $this->targetRepository->findOneBy(['beneficiary' => $beneficiary, 'assistance' => $this->assistanceRoot]);
        if ($target === null) return $this;

        if ($target->hasDistributionStarted()) {
            throw new RemoveBeneficiaryWithReliefException($target->getBeneficiary());
        }
        $target->setRemoved(true)
            ->setJustification($justification);
        $this->cancelUnusedReliefPackages([$target]);
        $this->assistanceRoot->setUpdatedOn(new \DateTime());
        $this->cleanCache();

        return $this;
    }

    public function save(): self
    {
        $this->entityManager->persist($this->assistanceRoot);
        $this->entityManager->flush();

        return $this;
    }

    private function cleanCache(): void
    {
        if (!$this->assistanceRoot->getId()) return; // not persisted yet
        try {
            $this->cache->delete(CacheTarget::assistanceId($this->assistanceRoot->getId()));
        } catch (InvalidArgumentException $e) {
            // TODO: log but ignore
        }
    }

    /**
     * @return CommoditySummary[]
     */
    public function getCommoditiesSummary(): array
    {
        $commodities = [];
        foreach ($this->getTargets() as $target) {
            foreach ($target->getReliefPackages() as $package) {
                if (!isset($commodities[$package->getModalityType()])) {
                    $commodities[$package->getModalityType()] = [];
                }
                if (!isset($commodities[$package->getModalityType()][$package->getUnit()])) {
                    $commodities[$package->getModalityType()][$package->getUnit()] = 0;
                }
                $commodities[$package->getModalityType()][$package->getUnit()] += floatval($package->getAmountToDistribute());
            }
        }
        $summaries = [];
        foreach ($commodities as $modalityType => $values) {
            foreach ($values as $unit => $amount) {
                $summaries[] = new CommoditySummary($modalityType, $unit, $amount);
            }
        }
        return $summaries;
    }

}
