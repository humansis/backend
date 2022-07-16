<?php

namespace NewApiBundle\MapperDeprecated;

use NewApiBundle\Repository\BeneficiaryRepository;
use DistributionBundle\Entity\Assistance;
use DistributionBundle\Enum\AssistanceTargetType;
use DistributionBundle\Repository\AssistanceBeneficiaryRepository;
use NewApiBundle\Enum\ProductCategoryType;

/**
 * @deprecated TODO remove methods while removing DistributionBundle and ProjectBundle
 */
class AssistanceMapper
{
    const TARGET_TYPE_TO_TYPE_MAPPING = [
        AssistanceTargetType::INDIVIDUAL => 1,
        AssistanceTargetType::HOUSEHOLD => 0,
        AssistanceTargetType::COMMUNITY => 2,
        AssistanceTargetType::INSTITUTION => 3,
    ];

    /** @var BeneficiaryMapper */
    private $beneficiaryMapper;

    /** @var AssistanceBeneficiaryRepository */
    private $distributionBNFRepo;

    /**
     * @var BeneficiaryRepository
     */
    private $beneficiaryRepository;

    /**
     * AssistanceMapper constructor.
     *
     * @param BeneficiaryMapper               $beneficiaryMapper
     * @param AssistanceBeneficiaryRepository $distributionBNFRepo
     * @param BeneficiaryRepository           $beneficiaryRepository
     */
    public function __construct(
        BeneficiaryMapper $beneficiaryMapper,
        AssistanceBeneficiaryRepository $distributionBNFRepo,
        BeneficiaryRepository $beneficiaryRepository
    ) {
        $this->beneficiaryMapper = $beneficiaryMapper;
        $this->distributionBNFRepo = $distributionBNFRepo;
        $this->beneficiaryRepository = $beneficiaryRepository;
    }

    public function toMinimalArray(?Assistance $assistance): ?array
    {
        if (!$assistance) {
            return null;
        }

        return [
            'id' => $assistance->getId(),
            'name' => $assistance->getName(),
        ];
    }

    public function toMinimalArrays(iterable $assistances): iterable
    {
        foreach ($assistances as $assistance) {
            yield $this->toMinimalArray($assistance);
        }
    }

    public function toFullArray(?Assistance $assistance): ?array
    {
        if (!$assistance) {
            return null;
        }
        $assistanceArray = [
            'id' => $assistance->getId(),
            'name' => $assistance->getName(),
            'updated_on' => $assistance->getUpdatedOnDateTime()->format('d-m-Y H:i'),
            'date_distribution' => $assistance->getDateDistribution(),
            'date_expiration' => $assistance->getDateExpiration(),
            'location' => $assistance->getLocation(),
            'project' => $assistance->getProject(),
            'selection_criteria' => $this->transformSelectionCriteria($assistance->getSelectionCriteria()),
            'archived' => $assistance->getArchived(),
            'validated' => $assistance->getValidated(),
            'reporting_distribution' => $assistance->getReportingAssistance(),
            'type' => self::TARGET_TYPE_TO_TYPE_MAPPING[$assistance->getTargetType()] ?? null,
            'assistance_type' => $assistance->getAssistanceType(),
            'target_type' => $assistance->getTargetType(),
            'commodities' => $assistance->getCommodities(),
            'completed' => $assistance->getCompleted(),
            'beneficiaries_count' => $this->distributionBNFRepo->countActive($assistance),
            'sector' => $assistance->getSector(),
            'subsector' => $assistance->getSubSector(),
            'description' => $assistance->getDescription(),
            'households_targeted' => $assistance->getHouseholdsTargeted(),
            'individuals_targeted' => $assistance->getIndividualsTargeted(),
            'note' => $assistance->getNote(),
        ];

        return $assistanceArray;
    }

    public function toFullArrays(iterable $assistances): iterable
    {
        foreach ($assistances as $assistance) {
            yield $this->toFullArray($assistance);
        }
    }

    /**
     * @param Assistance|null $assistance
     *
     * @return array
     *
     * @deprecated this is too big so dont use it
     */
    public function toOldMobileArray(?Assistance $assistance): ?array
    {
        if (!$assistance) {
            return null;
        }

        $bnfs = $this->beneficiaryRepository->findByAssistance($assistance, null, null, null, [
            BeneficiaryRepository::BNF_ASSISTANCE_CONTEXT_REMOVED => 0,
            BeneficiaryRepository::BNF_ASSISTANCE_CONTEXT_ARCHIVED => 0,
        ]);

        $isFoodEnabled = in_array(ProductCategoryType::FOOD, $assistance->getAllowedProductCategoryTypes());
        $isNonFoodEnabled = in_array(ProductCategoryType::NONFOOD, $assistance->getAllowedProductCategoryTypes());
        $isCashbackEnabled = in_array(ProductCategoryType::CASHBACK, $assistance->getAllowedProductCategoryTypes());

        $assistanceArray = [
            'id' => $assistance->getId(),
            'name' => $assistance->getName(),
            'updated_on' => $assistance->getUpdatedOnDateTime()->format('d-m-Y H:i'),
            'date_distribution' => $assistance->getDateDistribution(),
            'date_expiration' => $assistance->getDateExpiration(),
            'location' => $assistance->getLocation(),
            'project' => $assistance->getProject(),
            'selection_criteria' => $assistance->getSelectionCriteria(),
            'archived' => $assistance->getArchived(),
            'validated' => $assistance->getValidated(),
            'reporting_distribution' => $assistance->getReportingAssistance(),
            'type' => AssistanceTargetType::INDIVIDUAL === $assistance->getTargetType() ? 1 : 0,
            'assistance_type' => $assistance->getAssistanceType(),
            'target_type' => $assistance->getTargetType(),
            'commodities' => $assistance->getCommodities(),
            'distribution_beneficiaries' => $this->beneficiaryMapper->toOldMobileArrays($bnfs),
            'completed' => $assistance->getCompleted(),
            'beneficiaries_count' => $this->distributionBNFRepo->countActive($assistance),
            'description' => $assistance->getDescription(),
            'households_targeted' => $assistance->getHouseholdsTargeted(),
            'individuals_targeted' => $assistance->getIndividualsTargeted(),
            'foodLimit' => $isFoodEnabled ? $assistance->getFoodLimit() : '0.00',
            'nonfoodLimit' => $isNonFoodEnabled ? $assistance->getNonFoodLimit() : '0.00',
            'cashbackLimit' => $isCashbackEnabled ? $assistance->getCashbackLimit() : '0.00',
            'remoteDistributionAllowed' => $assistance->isRemoteDistributionAllowed(),
        ];

        return $assistanceArray;
    }

    /**
     * @param iterable $assistances
     *
     * @return iterable
     *
     * @deprecated this is too big so dont use it
     */
    public function toOldMobileArrays(iterable $assistances): iterable
    {
        foreach ($assistances as $assistance) {
            yield $this->toOldMobileArray($assistance);
        }
    }

    /**
     * @param \NewApiBundle\Entity\Assistance\SelectionCriteria[] $criteria
     */
    private function transformSelectionCriteria(iterable $criteria)
    {
        $result = [];

        foreach ($criteria as $criterion) {
            $result[][] = $criterion;
        }

        return $result;
    }
}