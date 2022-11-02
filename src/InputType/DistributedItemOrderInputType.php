<?php

namespace InputType;

use Request\OrderInputType\AbstractSortInputType;

class DistributedItemOrderInputType extends AbstractSortInputType
{
    final public const SORT_BY_BENEFICIARY_ID = 'beneficiaryId';
    final public const SORT_BY_DISTRIBUTION_DATE = 'dateDistribution';
    final public const SORT_BY_AMOUNT = 'amount';

    protected function getValidNames(): array
    {
        return [
            self::SORT_BY_BENEFICIARY_ID,
            self::SORT_BY_DISTRIBUTION_DATE,
            self::SORT_BY_AMOUNT,
        ];
    }
}
