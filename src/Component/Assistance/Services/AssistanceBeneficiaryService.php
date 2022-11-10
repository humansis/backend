<?php

declare(strict_types=1);

namespace Component\Assistance\Services;

use Component\Assistance\CommodityAssignBuilder;
use Component\Assistance\Enum\CommodityDivision;
use Component\Assistance\Scoring\Model\ScoringProtocol;
use DateTime;
use Entity\AbstractBeneficiary;
use Entity\Assistance;
use Entity\Assistance\ReliefPackage;
use Entity\AssistanceBeneficiary;
use Entity\Beneficiary;
use Entity\Commodity;
use Entity\Household;
use Enum\AssistanceTargetType;
use Enum\CacheTarget;
use Exception\AssistanceTargetMismatchException;
use Exception\ManipulationOverValidatedAssistanceException;
use Exception\BeneficiaryAlreadyRemovedException;
use JsonException;
use LogicException;
use OutputType\Assistance\AssistanceBeneficiaryOperationOutputType;
use Psr\Cache\InvalidArgumentException;
use Repository\AssistanceBeneficiaryRepository;
use Symfony\Component\Workflow\Registry;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;
use Utils\Exception\RemoveBeneficiaryWithReliefException;
use Workflow\ReliefPackageTransitions;

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

    /** @var TranslatorInterface */
    private $translator;

    /**
     * @param AssistanceBeneficiaryRepository $assistanceBeneficiaryRepository
     * @param CacheInterface $cache
     * @param Registry $workflowRegistry
     * @param TranslatorInterface $translator
     */
    public function __construct(
        AssistanceBeneficiaryRepository $assistanceBeneficiaryRepository,
        CacheInterface $cache,
        Registry $workflowRegistry,
        TranslatorInterface $translator
    ) {
        $this->assistanceBeneficiaryRepository = $assistanceBeneficiaryRepository;
        $this->cache = $cache;
        $this->workflowRegistry = $workflowRegistry;
        $this->translator = $translator;
    }

    /**
     * @param Beneficiary[] $beneficiaries
     * @param array $documentNumbers
     * @param string $documentType
     *
     * @return AssistanceBeneficiaryOperationOutputType
     */
    public function prepareOutputForDocumentNumbers(
        array $beneficiaries,
        array $documentNumbers,
        string $documentType
    ): AssistanceBeneficiaryOperationOutputType {
        $output = new AssistanceBeneficiaryOperationOutputType($this->translator, $documentNumbers, $documentType);
        $beneficiaryDocuments = [];
        foreach ($beneficiaries as $beneficiary) {
            foreach ($beneficiary->getPerson()->getNationalIds() as $document) {
                if ($document->getIdType() === $documentType) {
                    $key = strtolower($document->getIdNumber());
                    $beneficiaryDocuments[$key] = $document->getIdNumber();
                }
            }
        }
        foreach ($documentNumbers as $documentNumber) {
            $key = strtolower($documentNumber);
            if (!key_exists($key, $beneficiaryDocuments)) {
                $output->addDocumentNotFound($documentNumber);
            }
        }

        return $output;
    }

    public function prepareOutputForBeneficiaryIds(
        array $beneficiaries,
        array $beneficiaryIds
    ): AssistanceBeneficiaryOperationOutputType {
        $output = new AssistanceBeneficiaryOperationOutputType($this->translator);
        $foundBeneficiaries = array_map(function (Beneficiary $beneficiary) {
            return $beneficiary->getId();
        }, $beneficiaries);

        $notFoundBeneficiaries = array_diff($beneficiaryIds, $foundBeneficiaries);
        foreach ($notFoundBeneficiaries as $notFoundBeneficiaryId) {
            $output->addNotFound(['beneficiaryId' => $notFoundBeneficiaryId]);
        }

        return $output;
    }

    /**
     * @param AssistanceBeneficiaryOperationOutputType $output
     * @param Assistance $assistance
     * @param Beneficiary[] $beneficiaries
     * @param string|null $justification
     * @param ScoringProtocol|null $vulnerabilityScore
     *
     * @return void
     */
    public function addBeneficiariesToAssistance(
        AssistanceBeneficiaryOperationOutputType $output,
        Assistance $assistance,
        array $beneficiaries,
        ?string $justification = null,
        ?ScoringProtocol $vulnerabilityScore = null
    ): AssistanceBeneficiaryOperationOutputType {
        if ($assistance->isValidated()) {
            throw new ManipulationOverValidatedAssistanceException(
                "It is not possible to add a beneficiary to validated and locked assistance"
            );
        }

        $assistanceBeneficiaries = [];
        foreach ($beneficiaries as $beneficiary) {
            try {
                $assistanceBeneficiaries[] = $this->addAssistanceBeneficiary(
                    $assistance,
                    $beneficiary,
                    $justification,
                    $vulnerabilityScore
                );
                $output->addBeneficiarySuccess($beneficiary);
            } catch (Throwable $ex) {
                $output->addBeneficiaryFailed($beneficiary, $ex->getMessage());
            }
        }

        $this->recountReliefPackages($assistance, $assistanceBeneficiaries);
        $assistance->setUpdatedOn(new DateTime());
        $this->cleanCache($assistance);

        return $output;
    }

    /**
     * @param Assistance $assistance
     * @param AbstractBeneficiary $beneficiary
     * @param string|null $justification
     * @param ScoringProtocol|null $vulnerabilityScore
     *
     * @return AssistanceBeneficiary|object|null
     * @throws JsonException
     */
    private function addAssistanceBeneficiary(
        Assistance $assistance,
        AbstractBeneficiary $beneficiary,
        ?string $justification = null,
        ?ScoringProtocol $vulnerabilityScore = null
    ) {
        if (
            $assistance->getTargetType() === AssistanceTargetType::HOUSEHOLD
            && !$beneficiary->isHead()
        ) {
            throw new AssistanceTargetMismatchException(
                'Beneficiary id ' . $beneficiary->getId() . ' is not head of household'
            );
        }

        $assistanceBeneficiary = $this->assistanceBeneficiaryRepository->findOneBy(
            ['beneficiary' => $beneficiary, 'assistance' => $assistance]
        );
        if (null === $assistanceBeneficiary) {
            $assistanceBeneficiary = (new AssistanceBeneficiary())
                ->setAssistance($assistance)
                ->setBeneficiary($beneficiary)
                ->setRemoved(false);
            $assistance->addAssistanceBeneficiary($assistanceBeneficiary);
            if (!is_null($vulnerabilityScore)) {
                $assistanceBeneficiary->setVulnerabilityScores($vulnerabilityScore);
            }
        } else {
            $assistanceBeneficiary->setRemoved(false);
        }
        if (!empty($justification)) {
            $assistanceBeneficiary->setJustification($justification);
        }

        return $assistanceBeneficiary;
    }

    /**
     * @param AssistanceBeneficiaryOperationOutputType $output
     * @param Assistance $assistance
     * @param Beneficiary $beneficiary
     * @param string|null $justification
     * @param ScoringProtocol|null $vulnerabilityScore
     *
     * @return void
     * @throws JsonException
     */
    public function addBeneficiaryToAssistance(
        AssistanceBeneficiaryOperationOutputType $output,
        Assistance $assistance,
        AbstractBeneficiary $beneficiary,
        ?string $justification = null,
        ?ScoringProtocol $vulnerabilityScore = null
    ): void {
        $this->addBeneficiariesToAssistance($output, $assistance, [$beneficiary], $justification, $vulnerabilityScore);
    }

    /**
     * @param AssistanceBeneficiaryOperationOutputType $output
     * @param Assistance $assistance
     * @param Beneficiary[] $beneficiaries
     * @param string $justification
     *
     * @return void
     */
    public function removeBeneficiariesFromAssistance(
        AssistanceBeneficiaryOperationOutputType $output,
        Assistance $assistance,
        array $beneficiaries,
        string $justification
    ): AssistanceBeneficiaryOperationOutputType {
        if ($assistance->isValidated()) {
            throw new ManipulationOverValidatedAssistanceException(
                'It is not possible to remove a beneficiary from validated and locked assistance'
            );
        }

        $targets = [];
        foreach ($beneficiaries as $beneficiary) {
            try {
                $assistanceBeneficiary = $this->removeAssistanceBeneficiary($assistance, $beneficiary, $justification);
                if ($assistanceBeneficiary !== null) {
                    $targets[] = $assistanceBeneficiary;
                    $output->addBeneficiarySuccess($beneficiary);
                } else {
                    $output->addBeneficiaryNotFound($beneficiary);
                }
            } catch (AssistanceTargetMismatchException $ex) {
                $output->addBeneficiaryMismatch($beneficiary);
            } catch (BeneficiaryAlreadyRemovedException $ex) {
                $output->addBeneficiaryAlreadyRemoved($beneficiary);
            } catch (Throwable $ex) {
                $output->addBeneficiaryFailed($beneficiary, $ex->getMessage());
            }
        }

        $assistance->setUpdatedOn(new DateTime());
        $this->cancelUnusedReliefPackages($assistance, $targets);
        $this->cleanCache($assistance);

        return $output;
    }

    /**
     * @param Assistance $assistance
     * @param Beneficiary $beneficiary
     * @param string $justification
     *
     * @return AssistanceBeneficiary|null
     */
    private function removeAssistanceBeneficiary(
        Assistance $assistance,
        Beneficiary $beneficiary,
        string $justification
    ): ?AssistanceBeneficiary {
        if (
            $assistance->getTargetType() === AssistanceTargetType::HOUSEHOLD
            && !$beneficiary->isHead()
        ) {
            throw new AssistanceTargetMismatchException();
        }
        $assistanceBeneficiary = $this->assistanceBeneficiaryRepository->findOneBy(
            ['beneficiary' => $beneficiary, 'assistance' => $assistance]
        );
        if ($assistanceBeneficiary !== null) {
            if ($assistanceBeneficiary->getRemoved()) {
                throw new BeneficiaryAlreadyRemovedException();
            }
            if ($assistanceBeneficiary->hasDistributionStarted()) {
                throw new RemoveBeneficiaryWithReliefException($assistanceBeneficiary->getBeneficiary());
            }
            $assistanceBeneficiary->setRemoved(true)
                ->setJustification($justification);
        }

        return $assistanceBeneficiary;
    }

    /**
     * @param AssistanceBeneficiaryOperationOutputType $output
     * @param Assistance $assistance
     * @param Beneficiary $beneficiary
     * @param string $justification
     *
     */
    public function removeBeneficiaryFromAssistance(
        AssistanceBeneficiaryOperationOutputType $output,
        Assistance $assistance,
        AbstractBeneficiary $beneficiary,
        string $justification
    ): void {
        $this->removeBeneficiariesFromAssistance($output, $assistance, [$beneficiary], $justification);
    }

    /**
     * @param Assistance $assistance
     * @param array|null $targets
     *
     * @return void
     */
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
            $modality = $commodity->getModalityType();
            $unit = $commodity->getUnit();

            if (!isset($modalityUnits[$modality])) {
                $modalityUnits[$modality] = [];
            }
            if (!in_array($unit, $modalityUnits[$commodity->getModalityType()])) {
                $modalityUnits[$commodity->getModalityType()][] = $commodity->getUnit();
            }
            if ($commodity->getDivision() !== null) {
                if ($assistance->getTargetType() !== AssistanceTargetType::HOUSEHOLD) {
                    throw new LogicException(
                        sprintf(
                            "'%s' division is meaningful only for %s assistance, not for %s.",
                            CommodityDivision::PER_HOUSEHOLD,
                            AssistanceTargetType::HOUSEHOLD,
                            $assistance->getTargetType()
                        )
                    );
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

    /**
     * @param Commodity $commodity
     * @param CommodityAssignBuilder $commodityBuilder
     *
     * @return CommodityAssignBuilder
     */
    private function addCommodityCallback(
        Commodity $commodity,
        CommodityAssignBuilder $commodityBuilder
    ): CommodityAssignBuilder {
        switch ($commodity->getDivision()) {
            case CommodityDivision::PER_HOUSEHOLD_MEMBER:
                $commodityBuilder = $this->addCommodityCallbackPerHouseholdMember($commodity, $commodityBuilder);
                break;
            case CommodityDivision::PER_HOUSEHOLD_MEMBERS:
                $commodityBuilder = $this->addCommodityCallbackPerHouseholdMembers($commodity, $commodityBuilder);
                break;
            case CommodityDivision::PER_HOUSEHOLD:
            default:
                $commodityBuilder->addCommodityValue(
                    $commodity->getModalityType(),
                    $commodity->getUnit(),
                    $commodity->getValue()
                );
                break;
        }

        return $commodityBuilder;
    }

    /**
     * @param Commodity $commodity
     * @param CommodityAssignBuilder $commodityBuilder
     *
     * @return CommodityAssignBuilder
     */
    private function addCommodityCallbackPerHouseholdMember(
        Commodity $commodity,
        CommodityAssignBuilder $commodityBuilder
    ): CommodityAssignBuilder {
        $commodityBuilder->addCommodityCallback(
            $commodity->getModalityType(),
            $commodity->getUnit(),
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

        return $commodityBuilder;
    }

    /**
     * @param Commodity $commodity
     * @param CommodityAssignBuilder $commodityBuilder
     *
     * @return CommodityAssignBuilder
     */
    private function addCommodityCallbackPerHouseholdMembers(
        Commodity $commodity,
        CommodityAssignBuilder $commodityBuilder
    ): CommodityAssignBuilder {
        $commodityBuilder->addCommodityCallback(
            $commodity->getModalityType(),
            $commodity->getUnit(),
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
                        ($divisionGroup->getRangeFrom(
                        ) <= $countOfBeneficiariesInHousehold) && ($countOfBeneficiariesInHousehold <= ($divisionGroup->getRangeTo(
                        ) ?? 1000))
                    ) {
                        return (float) $divisionGroup->getValue();
                    }
                }

                throw new LogicException("Division Group was not found.");
            }
        );

        return $commodityBuilder;
    }

    private function cleanCache(Assistance $assistance): void
    {
        if (!$assistance->getId()) {
            return;
        } // not persisted yet
        try {
            $this->cache->delete(CacheTarget::assistanceId($assistance->getId()));
        } catch (InvalidArgumentException $e) {
            // TODO: log but ignore
        }
    }
}
