<?php

namespace InputType;

use Request\OrderInputType\AbstractSortInputType;

class VendorOrderInputType extends AbstractSortInputType
{
    final public const SORT_BY_ID = 'id';
    final public const SORT_BY_SHOP = 'shop';
    final public const SORT_BY_NAME = 'name';
    final public const SORT_BY_USERNAME = 'username';
    final public const SORT_BY_ADDRESS_STREET = 'addressStreet';
    final public const SORT_BY_ADDRESS_NUMBER = 'addressNumber';
    final public const SORT_BY_ADDRESS_POSTCODE = 'addressPostcode';
    final public const SORT_BY_LOCATION = 'location';

    protected function getValidNames(): array
    {
        return [
            self::SORT_BY_ID,
            self::SORT_BY_SHOP,
            self::SORT_BY_NAME,
            self::SORT_BY_USERNAME,
            self::SORT_BY_ADDRESS_STREET,
            self::SORT_BY_ADDRESS_NUMBER,
            self::SORT_BY_ADDRESS_POSTCODE,
            self::SORT_BY_LOCATION,
        ];
    }
}
