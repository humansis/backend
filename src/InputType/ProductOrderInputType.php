<?php

declare(strict_types=1);

namespace InputType;

use Request\OrderInputType\AbstractSortInputType;

class ProductOrderInputType extends AbstractSortInputType
{
    final public const SORT_BY_ID = 'id';
    final public const SORT_BY_NAME = 'name';
    final public const SORT_BY_UNIT = 'unit';
    final public const SORT_BY_CATEGORY = 'productCategoryId';

    protected function getValidNames(): array
    {
        return [
            self::SORT_BY_ID,
            self::SORT_BY_NAME,
            self::SORT_BY_UNIT,
            self::SORT_BY_CATEGORY,
        ];
    }
}
