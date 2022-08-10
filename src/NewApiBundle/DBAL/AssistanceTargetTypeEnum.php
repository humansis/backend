<?php
declare(strict_types=1);

namespace NewApiBundle\DBAL;

use NewApiBundle\Enum\AssistanceTargetType;

final class AssistanceTargetTypeEnum extends AbstractEnum
{
    public function getName()
    {
        return 'enum_assistance_target_type';
    }

    public static function all(): array
    {
        return AssistanceTargetType::values();
    }
}
