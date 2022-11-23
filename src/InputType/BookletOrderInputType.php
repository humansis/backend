<?php

declare(strict_types=1);

namespace InputType;

use Request\OrderInputType\AbstractSortInputType;

class BookletOrderInputType extends AbstractSortInputType
{
    final public const SORT_BY_CODE = 'code';
    final public const SORT_BY_NUMBER_VOUCHERS = 'numberVouchers';
    final public const SORT_BY_VALUE = 'value';
    final public const SORT_BY_CURRENCY = 'currency';
    final public const SORT_BY_STATUS = 'status';
    final public const SORT_BY_BENEFICIARY = 'beneficiary';
    final public const SORT_BY_DISTRIBUTION = 'distribution';

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
