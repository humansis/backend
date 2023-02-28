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
