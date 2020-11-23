<?php
declare(strict_types=1);

namespace NewApiBundle\InputType;

use NewApiBundle\Request\OrderInputType\AbstractSortInputType;

class AssistanceOrderInputType extends AbstractSortInputType
{
    const SORT_BY_ID = 'id';
    const SORT_BY_NAME = 'name';
    const SORT_BY_LOCATION = 'location';
    const SORT_BY_DATE = 'date';
    const SORT_BY_TARGET = 'target';
    const SORT_BY_NUMBER_OF_BENEFICIARIES = 'bnfCount';

    protected function getValidNames(): array
    {
        return [
            self::SORT_BY_ID,
            self::SORT_BY_NAME,
            self::SORT_BY_LOCATION,
            self::SORT_BY_DATE,
            self::SORT_BY_TARGET,
            self::SORT_BY_NUMBER_OF_BENEFICIARIES,
        ];
    }
}
