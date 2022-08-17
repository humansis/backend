<?php
declare(strict_types=1);

namespace InputType;

use Request\OrderInputType\AbstractSortInputType;

class HouseholdOrderInputType extends AbstractSortInputType
{
    const SORT_BY_ID = 'id';
    const SORT_BY_NATIONAL_ID = 'nationalId';
    const SORT_BY_VULNERABILITIES = 'vulnerabilities';
    const SORT_BY_PROJECTS = 'projects';
    const SORT_BY_DEPENDENTS = 'dependents';
    const SORT_BY_LOCAL_FIRST_NAME = 'localFirstName';
    const SORT_BY_LOCAL_FAMILY_NAME = 'localFamilyName';
    const SORT_BY_CURRENT_HOUSEHOLD_LOCATION = 'currentHouseholdLocation';

    protected function getValidNames(): array
    {
        return [
            self::SORT_BY_ID,
            self::SORT_BY_NATIONAL_ID,
            self::SORT_BY_VULNERABILITIES,
            self::SORT_BY_PROJECTS,
            self::SORT_BY_DEPENDENTS,
            self::SORT_BY_LOCAL_FIRST_NAME,
            self::SORT_BY_LOCAL_FAMILY_NAME,
            self::SORT_BY_CURRENT_HOUSEHOLD_LOCATION,
        ];
    }
}
