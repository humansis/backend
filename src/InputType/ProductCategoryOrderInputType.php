<?php

declare(strict_types=1);

namespace InputType;

use Request\OrderInputType\AbstractSortInputType;

class ProductCategoryOrderInputType extends AbstractSortInputType
{
    final public const SORT_BY_ID = 'id';
    final public const SORT_BY_NAME = 'name';

    protected function getValidNames(): array
    {
        return [
            self::SORT_BY_ID,
            self::SORT_BY_NAME,
        ];
    }
}
