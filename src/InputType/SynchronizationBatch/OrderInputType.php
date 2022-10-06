<?php

declare(strict_types=1);

namespace InputType\SynchronizationBatch;

use Request\OrderInputType\AbstractSortInputType;

class OrderInputType extends AbstractSortInputType
{
    public const SORT_BY_ID = 'id';
    public const SORT_BY_TYPE = 'type';
    public const SORT_BY_SOURCE = 'source';
    public const SORT_BY_DATE = 'date';

    protected function getValidNames(): array
    {
        return [
            self::SORT_BY_ID,
            self::SORT_BY_TYPE,
            self::SORT_BY_SOURCE,
            self::SORT_BY_DATE,
        ];
    }
}
