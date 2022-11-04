<?php

declare(strict_types=1);

namespace DBAL;

use Enum\AssistanceTargetType;

final class AssistanceTargetTypeEnum extends AbstractEnum
{
    public function getName(): string
    {
        return 'enum_assistance_target_type';
    }

    public static function all(): array
    {
        return AssistanceTargetType::values();
    }
}
