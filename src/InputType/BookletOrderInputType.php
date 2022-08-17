<?php
declare(strict_types=1);

namespace InputType;

use Request\OrderInputType\AbstractSortInputType;

class BookletOrderInputType extends AbstractSortInputType
{
    const SORT_BY_CODE = 'code';
    const SORT_BY_NUMBER_VOUCHERS = 'numberVouchers';
    const SORT_BY_VALUE = 'value';
    const SORT_BY_CURRENCY = 'currency';
    const SORT_BY_STATUS = 'status';
    const SORT_BY_BENEFICIARY = 'beneficiary';
    const SORT_BY_DISTRIBUTION = 'distribution';

    protected function getValidNames(): array
    {
        return [
            self::SORT_BY_CODE,
            self::SORT_BY_NUMBER_VOUCHERS,
            self::SORT_BY_VALUE,
            self::SORT_BY_CURRENCY,
            self::SORT_BY_STATUS,
            self::SORT_BY_BENEFICIARY,
            self::SORT_BY_DISTRIBUTION,
        ];
    }
}

