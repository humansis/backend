<?php

declare(strict_types=1);

namespace Validator\Constraints;

use Component\Import\Enum\ImportCsoEnum;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class EmptyCountrySpecificsValidator extends ConstraintValidator
{
    /**
     * @param $value
     * @param EmptyCountrySpecifics $constraint
     * @return void
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!is_array($value)) {
            return;
        }

        foreach ($value as $key => $item) {
            $this->context->buildViolation($constraint->message)
                ->atPath($item[ImportCsoEnum::ImportLineEntityKey->value]->getFieldString())
                ->addViolation();
        }
    }
}
