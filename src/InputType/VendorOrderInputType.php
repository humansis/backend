<?php

namespace InputType;

use Request\OrderInputType\AbstractSortInputType;

class VendorOrderInputType extends AbstractSortInputType
{
    const SORT_BY_ID = 'id';
    const SORT_BY_SHOP = 'shop';
    const SORT_BY_NAME = 'name';
    const SORT_BY_USERNAME = 'username';
    const SORT_BY_ADDRESS_STREET = 'addressStreet';
    const SORT_BY_ADDRESS_NUMBER = 'addressNumber';
    const SORT_BY_ADDRESS_POSTCODE = 'addressPostcode';
    const SORT_BY_LOCATION = 'location';

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
