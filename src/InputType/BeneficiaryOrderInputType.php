<?php

declare(strict_types=1);

namespace InputType;

use Request\OrderInputType\AbstractSortInputType;

class BeneficiaryOrderInputType extends AbstractSortInputType
{
    final public const SORT_BY_ID = 'id';
    final public const SORT_BY_LOCAL_GIVEN_NAME = 'localGivenName';
    final public const SORT_BY_LOCAL_FAMILY_NAME = 'localFamilyName';
    final public const SORT_BY_NATIONAL_ID = 'nationalId';
    final public const SORT_BY_DISTRIBUTION_DATE = 'distributionDate';

    protected function getValidNames(): array
    {
        return [
            self::SORT_BY_ID,
            self::SORT_BY_LOCAL_GIVEN_NAME,
            self::SORT_BY_LOCAL_FAMILY_NAME,
            self::SORT_BY_NATIONAL_ID,
            self::SORT_BY_DISTRIBUTION_DATE,
        ];
    }
}
