<?php
declare(strict_types=1);

namespace NewApiBundle\InputType;

use NewApiBundle\Request\OrderInputType\AbstractSortInputType;

class ProductOrderInputType extends AbstractSortInputType
{
    const SORT_BY_ID = 'id';
    const SORT_BY_NAME = 'name';
    const SORT_BY_UNIT = 'unit';
    const SORT_BY_CATEGORY = 'productCategoryId';

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
