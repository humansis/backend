<?php

declare(strict_types=1);

namespace Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Utils\Phone\PrefixChecker;

class PhonePrefixValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint)
    {
        if (!$constraint instanceof PhonePrefix) {
            throw new UnexpectedTypeException($constraint, PhonePrefix::class);
        }

        if (empty($value)) {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }

        if (mb_substr($value, 0, 1) !== '+') {
            $this->context->buildViolation('Prefix value "{{ prefixValue }}" should start with symbol "+"')
                ->setParameter('{{ prefixValue }}', $value)
                ->addViolation();
        } else {
            if (!PrefixChecker::isPrefixValid($value)) {
                $this->context->buildViolation('Phone prefix should match any valid country prefix.')
                    ->addViolation();
            }
        }
    }
}
