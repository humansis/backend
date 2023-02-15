<?php

declare(strict_types=1);

namespace Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class DoesNotContainComma extends Constraint
{
    public string $message = 'The string "{{ string }}" can not contain comma.';
}
