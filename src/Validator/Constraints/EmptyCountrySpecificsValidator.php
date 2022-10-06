<?php

declare(strict_types=1);

namespace Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class EmptyCountrySpecificsValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (!is_array($value)) {
            return;
        }

        foreach ($value as $key => $item) {
            $this->context->buildViolation($constraint->message)
                ->atPath($key)
                ->addViolation();
        }
    }
}
