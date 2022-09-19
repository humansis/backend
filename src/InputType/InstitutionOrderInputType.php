<?php

namespace InputType;

use Request\OrderInputType\AbstractSortInputType;

class InstitutionOrderInputType extends AbstractSortInputType
{
    public const SORT_BY_ID = 'id';
    public const SORT_BY_NAME = 'name';
    public const SORT_BY_LONGITUDE = 'longitude';
    public const SORT_BY_LATITUDE = 'latitude';
    public const SORT_BY_CONTACT_GIVEN_NAME = 'contactGivenName';
    public const SORT_BY_CONTACT_FAMILY_NAME = 'contactFamilyName';
    public const SORT_BY_TYPE = 'type';

    protected function getValidNames(): array
    {
        return [
            self::SORT_BY_ID,
            self::SORT_BY_NAME,
            self::SORT_BY_LONGITUDE,
            self::SORT_BY_LATITUDE,
            self::SORT_BY_CONTACT_GIVEN_NAME,
            self::SORT_BY_CONTACT_FAMILY_NAME,
            self::SORT_BY_TYPE,
        ];
    }
}