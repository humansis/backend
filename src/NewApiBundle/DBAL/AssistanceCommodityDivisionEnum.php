<?php
declare(strict_types=1);

namespace NewApiBundle\DBAL;

use NewApiBundle\Component\Assistance\Enum\CommodityDivision;

class AssistanceCommodityDivisionEnum extends \NewApiBundle\DBAL\AbstractEnum
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
