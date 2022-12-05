<?php

declare(strict_types=1);

namespace Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class PhonePrefix extends Constraint
{
    public function getTargets(): array | string
    {
        return self::PROPERTY_CONSTRAINT;
    }
}
