<?php

namespace InputType;

use Request\OrderInputType\AbstractSortInputType;

class UserOrderInputType extends AbstractSortInputType
{
    public const SORT_BY_ID = 'id';
    public const SORT_BY_EMAIL = 'email';
    public const SORT_BY_RIGHTS = 'rights';
    public const SORT_BY_PREFIX = 'prefix';
    public const SORT_BY_PHONE = 'phone';

    protected function getValidNames(): array
    {
        return [
            self::SORT_BY_ID,
            self::SORT_BY_EMAIL,
            self::SORT_BY_RIGHTS,
            self::SORT_BY_PREFIX,
            self::SORT_BY_PHONE,
        ];
    }
}
