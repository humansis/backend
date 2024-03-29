<?php

namespace DBAL;

use Enum\AssistanceType;

class AssistanceTypeEnum extends AbstractEnum
{
    public function getName(): string
    {
        return 'enum_assistance_type';
    }

    public static function all(): array
    {
        return AssistanceType::values();
    }
}
