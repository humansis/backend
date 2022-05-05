<?php declare(strict_types=1);

namespace NewApiBundle\Component\Import\InvalidCell\ColumnSpecific;

use BeneficiaryBundle\Utils\HouseholdExportCSVService;

final class HeadSpecific implements ColumnSpecific
{

    public function getColumn(): string
    {
        return HouseholdExportCSVService::HEAD;
    }

    public function getValueCallback(): callable
    {
        return function ($value, string $type) {
            if (empty($value)) {
                return '0';
            }

            return $value;
        };
    }
}
