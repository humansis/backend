<?php
declare(strict_types=1);

namespace NewApiBundle\InputType;

use NewApiBundle\Request\OrderInputType\AbstractSortInputType;

class BeneficiaryOrderInputType extends AbstractSortInputType
{
    const SORT_BY_ID = 'id';
    const SORT_BY_LOCAL_GIVEN_NAME = 'localGivenName';
    const SORT_BY_LOCAL_FAMILY_NAME = 'localFamilyName';
    const SORT_BY_NATIONAL_ID = 'nationalId';
    const SORT_BY_DISTRIBUTION_DATE = 'distributionDate';

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
