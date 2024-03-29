<?php

namespace InputType;

use Request\OrderInputType\AbstractSortInputType;

class PurchasedItemOrderInputType extends AbstractSortInputType
{
    final public const SORT_BY_DATE_PURCHASE = 'datePurchase';
    final public const SORT_BY_VALUE = 'value';

    protected function getValidNames(): array
    {
        return [
            self::SORT_BY_DATE_PURCHASE,
            self::SORT_BY_VALUE,
        ];
    }
}
