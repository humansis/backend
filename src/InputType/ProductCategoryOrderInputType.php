<?php
declare(strict_types=1);

namespace InputType;

use Request\OrderInputType\AbstractSortInputType;

class ProductCategoryOrderInputType extends AbstractSortInputType
{
    const SORT_BY_ID = 'id';
    const SORT_BY_NAME = 'name';

    protected function getValidNames(): array
    {
        return [
            self::SORT_BY_ID,
            self::SORT_BY_NAME,
        ];
    }
}
