<?php

namespace NewApiBundle\InputType;

use NewApiBundle\Request\OrderInputType\AbstractSortInputType;

class ProjectOrderInputType extends AbstractSortInputType
{
    const SORT_BY_ID = 'id';
    const SORT_BY_ISO3 = 'iso3';
    const SORT_BY_NAME = 'name';
    const SORT_BY_NOTES = 'notes';
    const SORT_BY_START_DATE = 'startDate';
    const SORT_BY_END_DATE = 'endDate';
    const SORT_BY_TARGET = 'target';
    const SORT_BY_INTERNAL_ID = 'internalId';
    const SORT_BY_NUMBER_OF_HOUSEHOLDS = 'numberOfHouseholds';

    protected function getValidNames(): array
    {
        return [
            self::SORT_BY_ID,
            self::SORT_BY_ISO3,
            self::SORT_BY_NAME,
            self::SORT_BY_NOTES,
            self::SORT_BY_START_DATE,
            self::SORT_BY_END_DATE,
            self::SORT_BY_TARGET,
            self::SORT_BY_INTERNAL_ID,
        ];
    }
}
