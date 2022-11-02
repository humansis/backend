<?php

namespace InputType;

use Request\OrderInputType\AbstractSortInputType;

class InstitutionOrderInputType extends AbstractSortInputType
{
    final public const SORT_BY_ID = 'id';
    final public const SORT_BY_NAME = 'name';
    final public const SORT_BY_LONGITUDE = 'longitude';
    final public const SORT_BY_LATITUDE = 'latitude';
    final public const SORT_BY_CONTACT_GIVEN_NAME = 'contactGivenName';
    final public const SORT_BY_CONTACT_FAMILY_NAME = 'contactFamilyName';
    final public const SORT_BY_TYPE = 'type';

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
