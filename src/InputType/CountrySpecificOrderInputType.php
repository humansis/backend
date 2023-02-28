<?php

declare(strict_types=1);

namespace InputType;

use Request\OrderInputType\AbstractSortInputType;

class CountrySpecificOrderInputType extends AbstractSortInputType
{
    final public const SORT_BY_ID = 'id';
    final public const SORT_BY_FIELD = 'field';
    final public const SORT_BY_TYPE = 'type';

    protected function getValidNames(): array
    {
        return [
            self::SORT_BY_ID,
            self::SORT_BY_FIELD,
            self::SORT_BY_TYPE,
        ];
    }
}
