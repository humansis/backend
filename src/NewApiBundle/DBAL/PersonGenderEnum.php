<?php
declare(strict_types=1);

namespace NewApiBundle\DBAL;

use NewApiBundle\Enum\HouseholdAssets;
use NewApiBundle\Enum\PersonGender;

class PersonGenderEnum extends \CommonBundle\DBAL\AbstractEnum
{
    use EnumTrait;

    // unused yet, prepared for future migration
    public function getName(): string
    {
        return 'enum_person_gender';
    }

    public static function all(): array
    {
        return PersonGender::values();
    }

    public static function databaseMap(): array
    {
        return [
            0 => PersonGender::FEMALE,
            1 => PersonGender::MALE,
        ];
    }
}
