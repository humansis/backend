<?php

namespace InputType;

use Request\OrderInputType\AbstractSortInputType;

class VendorOrderInputType extends AbstractSortInputType
{
    public const SORT_BY_ID = 'id';
    public const SORT_BY_SHOP = 'shop';
    public const SORT_BY_NAME = 'name';
    public const SORT_BY_USERNAME = 'username';
    public const SORT_BY_ADDRESS_STREET = 'addressStreet';
    public const SORT_BY_ADDRESS_NUMBER = 'addressNumber';
    public const SORT_BY_ADDRESS_POSTCODE = 'addressPostcode';
    public const SORT_BY_LOCATION = 'location';

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
