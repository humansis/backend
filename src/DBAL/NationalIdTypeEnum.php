<?php

declare(strict_types=1);

namespace DBAL;

use Enum\NationalIdType;

class NationalIdTypeEnum extends AbstractEnum
{
    use EnumTrait;

    public function getName(): string
    {
        return 'enum_national_id_type';
    }

    public static function all(): array
    {
        return NationalIdType::values();
    }

    public static function databaseMap(): array
    {
        return [
            NationalIdType::NATIONAL_ID => NationalIdType::NATIONAL_ID,
            NationalIdType::TAX_NUMBER => NationalIdType::TAX_NUMBER,
            NationalIdType::PASSPORT => NationalIdType::PASSPORT,
            NationalIdType::FAMILY => NationalIdType::FAMILY,
            NationalIdType::BIRTH_CERTIFICATE => NationalIdType::BIRTH_CERTIFICATE,
            NationalIdType::DRIVERS_LICENSE => NationalIdType::DRIVERS_LICENSE,
            NationalIdType::CAMP_ID => NationalIdType::CAMP_ID,
            NationalIdType::SOCIAL_SERVICE_ID => NationalIdType::SOCIAL_SERVICE_ID,
            NationalIdType::OTHER => NationalIdType::OTHER,
            NationalIdType::CIVIL_REGISTRATION_RECORD => NationalIdType::CIVIL_REGISTRATION_RECORD,
        ];
    }
}
