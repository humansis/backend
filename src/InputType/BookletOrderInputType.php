<?php

declare(strict_types=1);

namespace InputType;

use Request\OrderInputType\AbstractSortInputType;

class BookletOrderInputType extends AbstractSortInputType
{
    public const SORT_BY_CODE = 'code';
    public const SORT_BY_NUMBER_VOUCHERS = 'numberVouchers';
    public const SORT_BY_VALUE = 'value';
    public const SORT_BY_CURRENCY = 'currency';
    public const SORT_BY_STATUS = 'status';
    public const SORT_BY_BENEFICIARY = 'beneficiary';
    public const SORT_BY_DISTRIBUTION = 'distribution';

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
