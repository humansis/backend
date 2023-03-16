<?php

declare(strict_types=1);

namespace Component\Assistance\Validator;

use InputType\Assistance\MoveAssistanceInputType;
use Symfony\Component\Validator\Constraint;

class AssistanceMove extends Constraint
{
    public MoveAssistanceInputType $moveAssistanceInputType;
}
