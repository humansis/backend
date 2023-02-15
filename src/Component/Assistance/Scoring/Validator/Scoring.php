<?php

declare(strict_types=1);

namespace Component\Assistance\Scoring\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
final class Scoring extends Constraint
{
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
