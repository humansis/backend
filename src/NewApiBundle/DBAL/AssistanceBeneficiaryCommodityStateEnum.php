<?php
declare(strict_types=1);

namespace NewApiBundle\DBAL;

use NewApiBundle\Enum\AssistanceBeneficiaryCommodityState;

class AssistanceBeneficiaryCommodityStateEnum extends \CommonBundle\DBAL\AbstractEnum
{
    public function getName(): string
    {
        return 'enum_assistance_beneficiary_commodity_state';
    }

    public static function all(): array
    {
        return AssistanceBeneficiaryCommodityState::values();
    }
}
