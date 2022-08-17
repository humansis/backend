<?php
declare(strict_types=1);

namespace InputType;

use Request\OrderInputType\AbstractSortInputType;

class CountrySpecificOrderInputType extends AbstractSortInputType
{
    const SORT_BY_ID = 'id';
    const SORT_BY_FIELD = 'field';
    const SORT_BY_TYPE = 'type';

    protected function getValidNames(): array
    {
        return [
            self::SORT_BY_ID,
            self::SORT_BY_FIELD,
            self::SORT_BY_TYPE,
        ];
    }
}
