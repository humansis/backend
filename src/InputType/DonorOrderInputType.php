<?php

declare(strict_types=1);

namespace InputType;

use Request\OrderInputType\AbstractSortInputType;

class DonorOrderInputType extends AbstractSortInputType
{
    final public const SORT_BY_ID = 'id';
    final public const SORT_BY_FULLNAME = 'fullname';
    final public const SORT_BY_SHORTNAME = 'shortname';
    final public const SORT_BY_DATE_ADDED = 'dateAdded';

    protected function getValidNames(): array
    {
        return [
            self::SORT_BY_ID,
            self::SORT_BY_FULLNAME,
            self::SORT_BY_SHORTNAME,
            self::SORT_BY_DATE_ADDED,
        ];
    }
}
