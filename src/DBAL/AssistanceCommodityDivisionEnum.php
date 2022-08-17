<?php
declare(strict_types=1);

namespace DBAL;

use Component\Assistance\Enum\CommodityDivision;

class AssistanceCommodityDivisionEnum extends \DBAL\AbstractEnum
{
    public static function all(): array
    {
        return CommodityDivision::values();
    }


    public function getName(): string
    {
        return 'enum_assitance_commodity_division';
    }
}
