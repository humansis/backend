<?php

namespace InputType;

use Request\OrderInputType\AbstractSortInputType;

class DistributedItemOrderInputType extends AbstractSortInputType
{
    const SORT_BY_BENEFICIARY_ID = 'beneficiaryId';
    const SORT_BY_DISTRIBUTION_DATE = 'dateDistribution';
    const SORT_BY_AMOUNT = 'amount';

    protected function getValidNames(): array
    {
        return [
            self::SORT_BY_BENEFICIARY_ID,
            self::SORT_BY_DISTRIBUTION_DATE,
            self::SORT_BY_AMOUNT,
        ];
    }
}
