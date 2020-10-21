<?php

namespace ProjectBundle\DBAL;

use CommonBundle\DBAL\AbstractEnum;
use ProjectBundle\Enum\Livelihood;

class LivelihoodEnum extends AbstractEnum
{
    public function getName()
    {
        return 'enum_livelihood';
    }

    public static function all(): array
    {
        return Livelihood::values();
    }
}
