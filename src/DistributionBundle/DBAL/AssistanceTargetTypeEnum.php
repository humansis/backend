<?php
declare(strict_types=1);

namespace DistributionBundle\DBAL;

use CommonBundle\DBAL\AbstractEnum;
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
