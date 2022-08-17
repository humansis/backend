<?php
declare(strict_types=1);

namespace InputType;

use Request\OrderInputType\AbstractSortInputType;

class AssistanceOrderInputType extends AbstractSortInputType
{
    const SORT_BY_ID = 'id';
    const SORT_BY_NAME = 'name';
    const SORT_BY_LOCATION = 'location';
    const SORT_BY_PROJECT = 'project';
    const SORT_BY_MODALITY_TYPE = 'modalityType';
    const SORT_BY_DATE = 'dateDistribution';
    const SORT_BY_DATE_EXPIRATION = 'dateExpiration';
    const SORT_BY_TARGET = 'target';
    const SORT_BY_NUMBER_OF_BENEFICIARIES = 'bnfCount';
    const SORT_BY_VALUE = 'value';
    const SORT_BY_UNIT = 'unit';
    const SORT_BY_TYPE = 'type';

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
            self::SORT_BY_VALUE,
            self::SORT_BY_UNIT,
            self::SORT_BY_TYPE,
        ];
    }
}
