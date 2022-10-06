<?php

declare(strict_types=1);

namespace InputType;

use Request\OrderInputType\AbstractSortInputType;

class ProjectOrderInputType extends AbstractSortInputType
{
    public const SORT_BY_ID = 'id';
    public const SORT_BY_NAME = 'name';
    public const SORT_BY_INTERNAL_ID = 'internalId';
    public const SORT_BY_START_DATE = 'startDate';
    public const SORT_BY_END_DATE = 'endDate';
    public const SORT_BY_NUMBER_OF_HOUSEHOLDS = 'numberOfHouseholds';

    protected function getValidNames(): array
    {
        return [
            self::SORT_BY_ID,
            self::SORT_BY_NAME,
            self::SORT_BY_INTERNAL_ID,
            self::SORT_BY_START_DATE,
            self::SORT_BY_END_DATE,
            self::SORT_BY_NUMBER_OF_HOUSEHOLDS,
        ];
    }
}
