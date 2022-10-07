<?php

declare(strict_types=1);

namespace Component\Assistance\Domain;

use DateTime;
use DateTimeImmutable;
use Entity\AbstractBeneficiary;
use Entity\Beneficiary;
use Entity\Household;
use Entity;
use Entity\AssistanceBeneficiary;
use Entity\User;
use Enum\AssistanceTargetType;
use Entity\DivisionGroup;
use LogicException;
use Repository\AssistanceBeneficiaryRepository;
use Utils\Exception\RemoveBeneficiaryWithReliefException;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\NoResultException;
use Component\Assistance\CommodityAssignBuilder;
use Component\Assistance\DTO\CommoditySummary;
use Component\Assistance\DTO\CriteriaGroup;
use Component\Assistance\Enum\CommodityDivision;
use Component\Assistance\Scoring\Model\ScoringProtocol;
use Component\Assistance\SelectionCriteriaFactory;
use Entity\Assistance\ReliefPackage;
use Enum\CacheTarget;
use Exception\ManipulationOverValidatedAssistanceException;
use InputType\Assistance\CommodityInputType;
use Repository\AssistanceStatisticsRepository;
use Workflow\ReliefPackageTransitions;
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

    /** @var AssistanceStatisticsRepository */
    private $assistanceStatisticRepository;

    /** @var AssistanceBeneficiaryRepository */
    private $targetRepository;

    /** @var Registry $workflowRegistry */
    private $workflowRegistry;

    /** @var SelectionCriteriaFactory */
    private $selectionCriteriaFactory;

    /**
     * @param Entity\Assistance $assistanceEntity
     * @param CacheInterface $cache
     * @param AssistanceStatisticsRepository $assistanceStatisticRepository
     * @param Registry $workflowRegistry
     * @param AssistanceBeneficiaryRepository $targetRepository
     * @param SelectionCriteriaFactory $selectionCriteriaFactory
     */
    public function __construct(
        Entity\Assistance $assistanceEntity,
        CacheInterface $cache,
        AssistanceStatisticsRepository $assistanceStatisticRepository,
        Registry $workflowRegistry,
        AssistanceBeneficiaryRepository $targetRepository,
        SelectionCriteriaFactory $selectionCriteriaFactory
    ) {
        $this->assistanceRoot = $assistanceEntity;
        $this->cache = $cache;
        $this->assistanceStatisticRepository = $assistanceStatisticRepository;
        $this->workflowRegistry = $workflowRegistry;
        $this->targetRepository = $targetRepository;
        $this->selectionCriteriaFactory = $selectionCriteriaFactory;
    }

    public function getStatistics(?string $countryIso3 = null): array
    {
        $key = CacheTarget::assistanceId($this->assistanceRoot->getId() ?? 'new');

        return $this->cache->get($key, function (ItemInterface $item) use ($countryIso3) {
            try {
                $statistics = $this->assistanceStatisticRepository->findByAssistance(
                    $this->assistanceRoot,
                    $countryIso3
                );
            } catch (NoResultException $noResultException) {
                throw new NotFoundHttpException(
                    "Assistance {$this->assistanceRoot->getId()} is not in country $countryIso3"
                );
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

    public function validate(User $user): self
    {
        $this->cleanCache();
        $this->assistanceRoot->setValidatedBy($user);
        $this->assistanceRoot->setUpdatedOn(new DateTimeImmutable());
        $this->recountReliefPackages();

        return $this;
    }

    public function unvalidate(): self
    {
        if (!$this->assistanceRoot->isValidated()) {
            throw new \InvalidArgumentException('Unable to unvalidate the assistance. Assistance wasn\'t validated.');
        }
        $this->cleanCache();

        $statistics = $this->getStatistics();

        if ($statistics['amountDistributed'] > 0) {
            throw new \InvalidArgumentException('Unable to unvalidate the assistance. Assistance is already started.');
        }
        $this->assistanceRoot->setValidatedBy(null);
        $this->assistanceRoot->setUpdatedOn(new DateTimeImmutable());

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
        if ($this->assistanceRoot->isValidated()) {
            throw new LogicException('Validated assistance shouldn\'t be edited');
        }

        $commodity = new Entity\Commodity();
        $commodity->setModalityType($commodityInputType->getModalityType());
        $commodity->setDescription($commodityInputType->getDescription());
        $commodity->setValue($commodityInputType->getValue());
        $commodity->setUnit($commodityInputType->getUnit());
        if ($commodityInputType->getDivision()) {
            $commodity->setDivision($commodityInputType->getDivision()->getCode());
            if ($commodityInputType->getDivision()->getQuantities()) {
                foreach ($commodityInputType->getDivision()->getQuantities() as $quantity) {
                    $divisionGroup = new DivisionGroup();
                    $divisionGroup->setRangeFrom($quantity->getRangeFrom());
                    $divisionGroup->setRangeTo($quantity->getRangeTo());
                    $divisionGroup->setValue((string) $quantity->getValue());
                    $commodity->addDivisionGroup($divisionGroup);
                }
            }
        } else {
            $commodity->setDivision(null);
        }
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
            $modality = $commodity->getModalityType();
            $unit = $commodity->getUnit();

            if (!isset($modalityUnits[$modality])) {
                $modalityUnits[$modality] = [];
            }
            if (!in_array($unit, $modalityUnits[$commodity->getModalityType()])) {
                $modalityUnits[$commodity->getModalityType()][] = $commodity->getUnit();
            }
            if ($commodity->getDivision() !== null) {
                if ($this->assistanceRoot->getTargetType() !== AssistanceTargetType::HOUSEHOLD) {
                    throw new LogicException(
                        sprintf(
                            "'%s' division is meaningful only for %s assistance, not for %s.",
                            CommodityDivision::PER_HOUSEHOLD,
                            AssistanceTargetType::HOUSEHOLD,
                            $this->assistanceRoot->getTargetType()
                        )
                    );
                }
            }
            switch ($commodity->getDivision()) {
                case CommodityDivision::PER_HOUSEHOLD_MEMBER:
                    $commodityBuilder->addCommodityCallback(
                        $modality,
                        $unit,
                        function (AssistanceBeneficiary $target) use ($commodity) {
                            /** @var Household $household */
                            $household = $target->getBeneficiary();

                            // fallback for HH assistances directed to HHHs
                            if ($household instanceof Beneficiary) {
                                $household = $household->getHousehold();
                            }

                            return $commodity->getValue() * count($household->getBeneficiaries());
                        }
                    );
                    break;
                case CommodityDivision::PER_HOUSEHOLD_MEMBERS:
                    $commodityBuilder->addCommodityCallback(
                        $modality,
                        $unit,
                        function (AssistanceBeneficiary $target) use ($commodity) {
                            /** @var Household $household */
                            $household = $target->getBeneficiary();

                            // fallback for HH assistances directed to HHHs
                            if ($household instanceof Beneficiary) {
                                $household = $household->getHousehold();
                            }

                            $countOfBeneficiariesInHousehold = $household->getBeneficiaries()->count();
                            foreach ($commodity->getDivisionGroups() as $divisionGroup) {
                                if (
                                    ($divisionGroup->getRangeFrom() <= $countOfBeneficiariesInHousehold)
                                    && ($countOfBeneficiariesInHousehold <= ($divisionGroup->getRangeTo() ?? 1000))
                                ) {
                                    return (float) $divisionGroup->getValue();
                                }
                            }

                            throw new LogicException("Division Group was not found.");
                        }
                    );
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

        $this->cleanCache();
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

                if ($reliefPackageWorkflow->can($reliefPackage, ReliefPackageTransitions::CANCEL)) {
                    $reliefPackageWorkflow->apply($reliefPackage, ReliefPackageTransitions::CANCEL);
                }
            }
        }
    }

    /**
     * @param AbstractBeneficiary $beneficiary
     * @param string|null $justification
     * @param ScoringProtocol|null $vulnerabilityScore
     *
     * @return Assistance
     */
    public function addBeneficiary(
        AbstractBeneficiary $beneficiary,
        ?string $justification = null,
        ?ScoringProtocol $vulnerabilityScore = null
    ): self {
        if ($this->assistanceRoot->isValidated()) {
            throw new ManipulationOverValidatedAssistanceException(
                "It is not possible to add a beneficiary to validated and locked assistance"
            );
        }

        $target = $this->targetRepository->findOneBy(
            ['beneficiary' => $beneficiary, 'assistance' => $this->assistanceRoot]
        );
        if (null === $target) {
            $target = (new AssistanceBeneficiary())
                ->setAssistance($this->assistanceRoot)
                ->setBeneficiary($beneficiary)
                ->setRemoved(false);
            $this->assistanceRoot->addAssistanceBeneficiary($target);
            if (!is_null($vulnerabilityScore)) {
                $target->setVulnerabilityScores($vulnerabilityScore);
            }
        } else {
            $target->setRemoved(false);
        }

        if (!empty($justification)) {
            $target->setJustification($justification);
        }
        $this->recountReliefPackages([$target]);
        $this->assistanceRoot->setUpdatedOn(new DateTime());
        $this->cleanCache();

        return $this;
    }

    /**
     * @param AbstractBeneficiary $beneficiary
     * @param string $justification
     *
     * @return $this
     */
    public function removeBeneficiary(AbstractBeneficiary $beneficiary, string $justification): self
    {
        if ($this->assistanceRoot->isValidated()) {
            throw new ManipulationOverValidatedAssistanceException(
                'It is not possible to remove a beneficiary from validated and locked assistance'
            );
        }

        /** @var AssistanceBeneficiary $target */
        $target = $this->targetRepository->findOneBy(
            ['beneficiary' => $beneficiary, 'assistance' => $this->assistanceRoot]
        );
        if ($target === null) {
            return $this;
        }

        if ($target->hasDistributionStarted()) {
            throw new RemoveBeneficiaryWithReliefException($target->getBeneficiary());
        }
        $target->setRemoved(true)
            ->setJustification($justification);
        $this->assistanceRoot->setUpdatedOn(new DateTime());

        $this->cancelUnusedReliefPackages([$target]);

        $this->cleanCache();

        return $this;
    }

    private function cleanCache(): void
    {
        if (!$this->assistanceRoot->getId()) {
            return;
        } // not persisted yet
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
                $commodities[$package->getModalityType()][$package->getUnit()] += floatval(
                    $package->getAmountToDistribute()
                );
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

    /**
     * Get all active beneficiaries (not removed or archived)
     *
     * @return ArrayCollection
     */
    public function getBeneficiaries(): ArrayCollection
    {
        return $this->getAssistanceRoot()->getDistributionBeneficiaries()->filter(function ($item) {
            /**
             * @var AssistanceBeneficiary $item
             */
            return ($item->getBeneficiary()->getArchived() === false) && ($item->getRemoved() === false);
        });
    }

    public function addSelectionCriteria(SelectionCriteria $selectionCriteria): void
    {
        $this->assistanceRoot
            ->getAssistanceSelection()
            ->getSelectionCriteria()
            ->add($selectionCriteria->getCriteriaRoot());
        $selectionCriteria
            ->getCriteriaRoot()
            ->setAssistanceSelection($this->assistanceRoot->getAssistanceSelection());
    }

    /**
     * @return CriteriaGroup[]
     */
    public function getSelectionCriteriaGroups(): iterable
    {
        $selectionCriteria = [];
        /** @var \Entity\Assistance\SelectionCriteria $criterion */
        foreach ($this->assistanceRoot->getSelectionCriteria() as $criterion) {
            $selectionCriteria[$criterion->getGroupNumber()][] = $this->selectionCriteriaFactory->hydrate($criterion);
        }
        foreach ($selectionCriteria as $groupNumber => $criteria) {
            yield new CriteriaGroup($groupNumber, $criteria);
        }
    }
}
