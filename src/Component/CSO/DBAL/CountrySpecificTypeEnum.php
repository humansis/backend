<?php

declare(strict_types=1);

namespace Component\CSO\DBAL;

use Component\CSO\Enum\CountrySpecificType;
use DBAL\AbstractEnum;

final class CountrySpecificTypeEnum extends AbstractEnum
{
    public static function all(): array
    {
        return CountrySpecificType::values();
    }

    public function getName(): string
    {
        return 'enum_country_specific_type';
    }
}
