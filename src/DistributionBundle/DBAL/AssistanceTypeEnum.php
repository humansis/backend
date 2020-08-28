<?php

namespace DistributionBundle\DBAL;

use CommonBundle\DBAL\AbstractEnum;

class AssistanceTypeEnum extends AbstractEnum
{
    protected $name = 'assistance_type_enum';

    const DISTRIBUTION = 'distribution';
    const ACTIVITY = 'activity';

    protected static $values = [
        self::ACTIVITY,
        self::DISTRIBUTION,
    ];

    public function all(): array
    {
        return self::$values;
    }
}
