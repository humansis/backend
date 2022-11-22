<?php

declare(strict_types=1);

namespace InputType\Import;

use Request\OrderInputType\AbstractSortInputType;

class OrderInputType extends AbstractSortInputType
{
    public const SORT_BY_ID = 'id';
    public const SORT_BY_TITLE = 'title';
    public const SORT_BY_DESCRIPTION = 'description';
    public const SORT_BY_PROJECT = 'project';
    public const SORT_BY_STATUS = 'status';
    public const SORT_BY_CREATED_BY = 'createdBy';
    public const SORT_BY_CREATED_AT = 'createdAt';

    protected function getValidNames(): array
    {
        return [
            self::SORT_BY_ID,
            self::SORT_BY_TITLE,
            self::SORT_BY_DESCRIPTION,
            self::SORT_BY_PROJECT,
            self::SORT_BY_STATUS,
            self::SORT_BY_CREATED_BY,
            self::SORT_BY_CREATED_AT,
        ];
    }
}
