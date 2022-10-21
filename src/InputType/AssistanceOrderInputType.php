<?php

declare(strict_types=1);

namespace InputType;

use Request\OrderInputType\AbstractSortInputType;

class AssistanceOrderInputType extends AbstractSortInputType
{
    final public const SORT_BY_ID = 'id';
    final public const SORT_BY_NAME = 'name';
    final public const SORT_BY_LOCATION = 'location';
    final public const SORT_BY_PROJECT = 'project';
    final public const SORT_BY_MODALITY_TYPE = 'modalityType';
    final public const SORT_BY_DATE = 'dateDistribution';
    final public const SORT_BY_DATE_EXPIRATION = 'dateExpiration';
    final public const SORT_BY_TARGET = 'target';
    final public const SORT_BY_NUMBER_OF_BENEFICIARIES = 'bnfCount';
    final public const SORT_BY_ROUND = 'round';
    final public const SORT_BY_STATE = 'state';
    final public const SORT_BY_VALUE = 'value';
    final public const SORT_BY_UNIT = 'unit';
    final public const SORT_BY_TYPE = 'type';

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
