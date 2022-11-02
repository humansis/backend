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
    final public const TARGET_TYPE_TO_TYPE_MAPPING = [
        AssistanceTargetType::INDIVIDUAL => 1,
        AssistanceTargetType::HOUSEHOLD => 0,
        AssistanceTargetType::COMMUNITY => 2,
        AssistanceTargetType::INSTITUTION => 3,
    ];

    /**
     * AssistanceMapper constructor.
     */
    public function __construct(private readonly BeneficiaryMapper $beneficiaryMapper, private readonly AssistanceBeneficiaryRepository $distributionBNFRepo, private readonly BeneficiaryRepository $beneficiaryRepository)
    {
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
