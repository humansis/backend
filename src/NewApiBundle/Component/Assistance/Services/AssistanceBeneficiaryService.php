<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Assistance\Services;

use BeneficiaryBundle\Entity\AbstractBeneficiary;
use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Household;
use DistributionBundle\Entity\Assistance;
use DistributionBundle\Entity\AssistanceBeneficiary;
use DistributionBundle\Entity\Commodity;
use DistributionBundle\Enum\AssistanceTargetType;
use DistributionBundle\Repository\AssistanceBeneficiaryRepository;
use DistributionBundle\Utils\Exception\RemoveBeneficiaryWithReliefException;
use NewApiBundle\Component\Assistance\CommodityAssignBuilder;
use NewApiBundle\Component\Assistance\Enum\CommodityDivision;
use NewApiBundle\Component\Assistance\Scoring\Model\ScoringProtocol;
use NewApiBundle\Entity\Assistance\ReliefPackage;
use NewApiBundle\Enum\CacheTarget;
use NewApiBundle\Exception\ManipulationOverValidatedAssistanceException;
use NewApiBundle\Workflow\ReliefPackageTransitions;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Workflow\Registry;
use Symfony\Contracts\Cache\CacheInterface;

class AssistanceBeneficiaryService
{

    /**
     * @var AssistanceBeneficiaryRepository
     */
    private $assistanceBeneficiaryRepository;

    /** @var CacheInterface */
    private $cache;

    /** @var Registry $workflowRegistry */
    private $workflowRegistry;

    /**
     * @param AssistanceBeneficiaryRepository $assistanceBeneficiaryRepository
     * @param CacheInterface                  $cache
     * @param Registry                        $workflowRegistry
     */
    public function __construct(
        AssistanceBeneficiaryRepository $assistanceBeneficiaryRepository,
        CacheInterface                  $cache,
        Registry                        $workflowRegistry
    )
    {
        $this->assistanceBeneficiaryRepository = $assistanceBeneficiaryRepository;
        $this->cache = $cache;
        $this->workflowRegistry = $workflowRegistry;
    }

    /**
     * @param Assistance           $assistance
     * @param array                $beneficiaries
     * @param string|null          $justification
     * @param ScoringProtocol|null $vulnerabilityScore
     *
     * @return void
     * @throws \JsonException
     */
    public function addBeneficiariesToAssistance(
        Assistance $assistance,
        array $beneficiaries,
        ?string $justification = null,
        ?ScoringProtocol $vulnerabilityScore = null
    ): void {

        if ($assistance->isValidated()) {
            throw new ManipulationOverValidatedAssistanceException("It is not possible to add a beneficiary to validated and locked assistance");
        }

        $targets = [];
        foreach ($beneficiaries as $beneficiary) {
            $target = $this->assistanceBeneficiaryRepository->findOneBy(['beneficiary' => $beneficiary, 'assistance' => $assistance]);
            if (null === $target) {
                $target = (new AssistanceBeneficiary())
                    ->setAssistance($assistance)
                    ->setBeneficiary($beneficiary)
                    ->setRemoved(false);
                $assistance->addAssistanceBeneficiary($target);
                if (!is_null($vulnerabilityScore)) {
                    $target->setVulnerabilityScores($vulnerabilityScore);
                }
            } else {
                $target->setRemoved(false);
            }

            if (!empty($justification)) {
                $target->setJustification($justification);
            }
            $targets[] = $target;
        }

        $this->recountReliefPackages($assistance, $targets);
        $assistance->setUpdatedOn(new \DateTime());
        $this->cleanCache($assistance);

    }

    /**
     * @param Assistance           $assistance
     * @param AbstractBeneficiary  $beneficiary
     * @param string|null          $justification
     * @param ScoringProtocol|null $vulnerabilityScore
     *
     * @return void
     */
    public function addBeneficiaryToAssistance(Assistance $assistance, AbstractBeneficiary $beneficiary, ?string $justification = null, ?ScoringProtocol $vulnerabilityScore = null): void
    {
        $this->addBeneficiariesToAssistance($assistance, [$beneficiary], $justification, $vulnerabilityScore);
    }

    /**
     * @param Assistance $assistance
     * @param array      $beneficiaries
     * @param string     $justification
     *
     * @return void
     */
    public function removeBeneficiariesFromAssistance(Assistance $assistance, array $beneficiaries, string $justification): void
    {
        if ($assistance->isValidated()) {
            throw new ManipulationOverValidatedAssistanceException('It is not possible to remove a beneficiary from validated and locked assistance');
        }

        $targets = [];
        foreach ($beneficiaries as $beneficiary) {
            /** @var AssistanceBeneficiary $target */
            $target = $this->assistanceBeneficiaryRepository->findOneBy(['beneficiary' => $beneficiary, 'assistance' => $assistance]);
            if ($target === null) {
                continue;
            }

            if ($target->hasDistributionStarted()) {
                throw new RemoveBeneficiaryWithReliefException($target->getBeneficiary());
            }
            $target->setRemoved(true)
                ->setJustification($justification);
            $targets[] = $target;
        }

        $assistance->setUpdatedOn(new \DateTime());
        $this->cancelUnusedReliefPackages($assistance, $targets);
        $this->cleanCache($assistance);

    }

    /**
     * @param Assistance          $assistance
     * @param AbstractBeneficiary $beneficiary
     * @param string              $justification
     *
     * @return AssistanceBeneficiaryService
     */
    public function removeBeneficiaryFromAssistance(Assistance $assistance, AbstractBeneficiary $beneficiary, string $justification): void
    {
       $this->removeBeneficiariesFromAssistance($assistance, [$beneficiary], $justification);
    }

    private function cancelUnusedReliefPackages(Assistance $assistance, ?array $targets = null): void
    {
        /** @var AssistanceBeneficiary $assistanceBeneficiary */
        foreach ($targets ?? $assistance->getDistributionBeneficiaries() as $assistanceBeneficiary) {
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
     * @param array|null $targets who should be recounted, null => all targets in assistance
     */
    private function recountReliefPackages(Assistance $assistance, ?array $targets = null): void
    {
        $modalityUnits = [];
        $commodityBuilder = new CommodityAssignBuilder();
        foreach ($assistance->getCommodities() as $commodity) {
            $modality = $commodity->getModalityType()->getName();
            $unit = $commodity->getUnit();

            if (!isset($modalityUnits[$modality])) {
                $modalityUnits[$modality] = [];
            }
            if (!in_array($unit, $modalityUnits[$commodity->getModalityType()->getName()])) {
                $modalityUnits[$commodity->getModalityType()->getName()][] = $commodity->getUnit();
            }
            if ($commodity->getDivision() !== null) {
                if ($assistance->getTargetType() !== AssistanceTargetType::HOUSEHOLD) {
                    throw new \LogicException(sprintf("'%s' division is meaningful only for %s assistance, not for %s.",
                        CommodityDivision::PER_HOUSEHOLD,
                        AssistanceTargetType::HOUSEHOLD,
                        $assistance->getTargetType()
                    ));
                }
            }
            $commodityBuilder = $this->addCommodityCallback($commodity, $commodityBuilder);
        }


        foreach ($modalityUnits as $modalityName => $units) {
            foreach ($units as $unit) {
                foreach ($targets ?? $assistance->getDistributionBeneficiaries() as $target) {
                    $target->setCommodityToDistribute(
                        $modalityName,
                        $unit,
                        $commodityBuilder->getValue($target, $modalityName, $unit)
                    );
                }
            }
        }


    }

    private function addCommodityCallback(Commodity $commodity, CommodityAssignBuilder $commodityBuilder): CommodityAssignBuilder
    {

        switch ($commodity->getDivision()) {
            case CommodityDivision::PER_HOUSEHOLD_MEMBER:
                $commodityBuilder = $this->addCommodityCallbackPerHouseholdMember($commodity, $commodityBuilder);
                break;
            case CommodityDivision::PER_HOUSEHOLD_MEMBERS:
                $commodityBuilder = $this->addCommodityCallbackPerHouseholdMembers($commodity, $commodityBuilder);
                break;
            case CommodityDivision::PER_HOUSEHOLD:
            default:
                $commodityBuilder->addCommodityValue($commodity->getModalityType()->getName(), $commodity->getUnit(), $commodity->getValue());
                break;
        }
        return $commodityBuilder;
    }

    private function addCommodityCallbackPerHouseholdMember(Commodity $commodity, CommodityAssignBuilder $commodityBuilder): CommodityAssignBuilder
    {
        $commodityBuilder->addCommodityCallback($commodity->getModalityType()->getName(), $commodity->getUnit(), function (AssistanceBeneficiary $target) use ($commodity) {
            /** @var Household $household */
            $household = $target->getBeneficiary();

            // fallback for HH assistances directed to HHHs
            if ($household instanceof Beneficiary) {
                $household = $household->getHousehold();
            }
            return $commodity->getValue() * count($household->getBeneficiaries());
        });
        return $commodityBuilder;
    }

    private function addCommodityCallbackPerHouseholdMembers(Commodity $commodity, CommodityAssignBuilder $commodityBuilder): CommodityAssignBuilder
    {
        $commodityBuilder->addCommodityCallback($commodity->getModalityType()->getName(), $commodity->getUnit(), function (AssistanceBeneficiary $target) use ($commodity) {
            /** @var Household $household */
            $household = $target->getBeneficiary();

            // fallback for HH assistances directed to HHHs
            if ($household instanceof Beneficiary) {
                $household = $household->getHousehold();
            }

            $countOfBeneficiariesInHousehold = $household->getBeneficiaries()->count();
            foreach ($commodity->getDivisionGroups() as $divisionGroup) {
                if (($divisionGroup->getRangeFrom() <= $countOfBeneficiariesInHousehold) && ($countOfBeneficiariesInHousehold <= ($divisionGroup->getRangeTo() ?? 1000))) {
                    return (float) $divisionGroup->getValue();
                }
            }

            throw new \LogicException("Division Group was not found.");
        });
        return $commodityBuilder;
    }

    private function cleanCache(Assistance $assistance): void
    {
        if (!$assistance->getId()) return; // not persisted yet
        try {
            $this->cache->delete(CacheTarget::assistanceId($assistance->getId()));
        } catch (InvalidArgumentException $e) {
            // TODO: log but ignore
        }
    }

}
