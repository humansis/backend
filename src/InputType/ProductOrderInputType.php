<?php

declare(strict_types=1);

namespace InputType;

use Request\OrderInputType\AbstractSortInputType;

class ProductOrderInputType extends AbstractSortInputType
{
    public const SORT_BY_ID = 'id';
    public const SORT_BY_NAME = 'name';
    public const SORT_BY_UNIT = 'unit';
    public const SORT_BY_CATEGORY = 'productCategoryId';

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
