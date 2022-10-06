<?php

declare(strict_types=1);

namespace InputType;

use Request\OrderInputType\AbstractSortInputType;

class BeneficiaryOrderInputType extends AbstractSortInputType
{
    public const SORT_BY_ID = 'id';
    public const SORT_BY_LOCAL_GIVEN_NAME = 'localGivenName';
    public const SORT_BY_LOCAL_FAMILY_NAME = 'localFamilyName';
    public const SORT_BY_NATIONAL_ID = 'nationalId';
    public const SORT_BY_DISTRIBUTION_DATE = 'distributionDate';

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
