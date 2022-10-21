<?php

declare(strict_types=1);

namespace InputType;

use Request\OrderInputType\AbstractSortInputType;

class AssistanceOrderInputType extends AbstractSortInputType
{
    public const SORT_BY_ID = 'id';
    public const SORT_BY_NAME = 'name';
    public const SORT_BY_LOCATION = 'location';
    public const SORT_BY_PROJECT = 'project';
    public const SORT_BY_MODALITY_TYPE = 'modalityType';
    public const SORT_BY_DATE = 'dateDistribution';
    public const SORT_BY_DATE_EXPIRATION = 'dateExpiration';
    public const SORT_BY_TARGET = 'target';
    public const SORT_BY_NUMBER_OF_BENEFICIARIES = 'bnfCount';
    public const SORT_BY_ROUND = 'round';
    public const SORT_BY_STATE = 'state';
    public const SORT_BY_VALUE = 'value';
    public const SORT_BY_UNIT = 'unit';
    public const SORT_BY_TYPE = 'type';

    protected function getValidNames(): array
    {
        return [
            self::SORT_BY_ID,
            self::SORT_BY_NAME,
            self::SORT_BY_LOCATION,
            self::SORT_BY_PROJECT,
            self::SORT_BY_MODALITY_TYPE,
            self::SORT_BY_DATE,
            self::SORT_BY_DATE_EXPIRATION,
            self::SORT_BY_TARGET,
            self::SORT_BY_NUMBER_OF_BENEFICIARIES,
            self::SORT_BY_ROUND,
            self::SORT_BY_STATE,
            self::SORT_BY_VALUE,
            self::SORT_BY_UNIT,
            self::SORT_BY_TYPE,
        ];
    }
}
