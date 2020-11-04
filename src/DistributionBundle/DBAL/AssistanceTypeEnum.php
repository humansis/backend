<?php

namespace DistributionBundle\DBAL;

use CommonBundle\DBAL\AbstractEnum;

class AssistanceTypeEnum extends AbstractEnum
{
    const DISTRIBUTION = 'distribution';
    const ACTIVITY = 'activity';

    protected static $values = [
        self::ACTIVITY,
        self::DISTRIBUTION,
    ];

    public function getName()
    {
        return 'assistance_type_enum';
    }

    public static function all(): array
    {
        return self::$values;
    }
}
