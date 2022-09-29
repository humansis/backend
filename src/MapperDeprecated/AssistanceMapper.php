<?php

namespace MapperDeprecated;

use Repository\BeneficiaryRepository;
use Entity\Assistance;
use Enum\AssistanceTargetType;
use Repository\AssistanceBeneficiaryRepository;
use Enum\ProductCategoryType;

/**
 * @deprecated
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
}
