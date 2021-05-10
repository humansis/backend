<?php

namespace NewApiBundle\InputType;

use NewApiBundle\Request\OrderInputType\AbstractSortInputType;

class PurchasedItemOrderInputType extends AbstractSortInputType
{
    const SORT_BY_DATE_PURCHASE = 'datePurchase';
    const SORT_BY_VALUE = 'value';

    protected function getValidNames(): array
    {
        return [
            self::SORT_BY_DATE_PURCHASE,
            self::SORT_BY_VALUE,
        ];
    }
}
