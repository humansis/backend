<?php
declare(strict_types=1);

namespace Component\Assistance\Scoring\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
final class Scoring extends Constraint
{
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
