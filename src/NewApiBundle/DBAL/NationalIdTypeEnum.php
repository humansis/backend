<?php
declare(strict_types=1);

namespace NewApiBundle\DBAL;

use NewApiBundle\Enum\NationalIdType;

class NationalIdTypeEnum extends \CommonBundle\DBAL\AbstractEnum
{
    use EnumTrait;

    // unused yet, prepared for future migration
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
            NationalIdType::PASSPORT => NationalIdType::PASSPORT,
            NationalIdType::FAMILY => NationalIdType::FAMILY,
            NationalIdType::BIRTH_CERTIFICATE => NationalIdType::BIRTH_CERTIFICATE,
            NationalIdType::DRIVERS_LICENSE => NationalIdType::DRIVERS_LICENSE,
            NationalIdType::CAMP_ID => NationalIdType::CAMP_ID,
            NationalIdType::SOCIAL_SERVICE_ID => NationalIdType::SOCIAL_SERVICE_ID,
            NationalIdType::OTHER => NationalIdType::OTHER,
        ];
    }
}