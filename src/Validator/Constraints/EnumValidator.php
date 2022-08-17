<?php
declare(strict_types=1);

namespace Validator\Constraints;

use Enum\EnumTrait;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\InvalidArgumentException;

class EnumValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     * @throws ReflectionException
     */
    public function validate($value, Constraint $constraint)
    {
        if (null === $value) {
            return;
        }

        if (!is_string($constraint->enumClass)) {
            throw new InvalidArgumentException('Provided value for parameter "enumClass" has to be type string. Got ' . gettype($constraint->enumClass) . ' instead.');
        }

        if (!is_bool($constraint->includeAPIAlternatives)) {
            throw new InvalidArgumentException('Provided value for parameter "includeAPIAlternatives" has to be type bool. Got ' . gettype($constraint->includeAPIAlternatives) . ' instead.');
        }

        $reflection = new ReflectionClass($constraint->enumClass);

        $hasEnumTrait = false;
        foreach ($reflection->getTraitNames() as $traitName) {
            if ($traitName === EnumTrait::class) {
                $hasEnumTrait = true;
                break;
            }
        }

        if (!$hasEnumTrait) {
            throw new InvalidArgumentException("Provided enum class '{$constraint->enumClass}' has to use '" . EnumTrait::class . "' trait.");
        }

        $allowedValues = $constraint->enumClass::values();

        if ($constraint->includeAPIAlternatives) {
            /** @var string[][] $apiAlternatives */
            $apiAlternatives = $constraint->enumClass::apiAlternatives();

            if (!empty($apiAlternatives)) {
                $allowedValues = array_merge($allowedValues, ...array_values($apiAlternatives));
            }
        }

        $values = [];

        if ($constraint->array && is_string($value)) {
            $values = explode(',', $value);
        } else {
            $values[] = $value;
        }

        foreach ($values as $value) {
            $valueNormalized = EnumTrait::normalizeValue($value);

            $allowedValuesNormalized = array_map(function ($value) {
                return EnumTrait::normalizeValue($value);
            }, $allowedValues);

            if (!in_array($valueNormalized, $allowedValuesNormalized)) {
                $this->context->buildViolation($constraint->message)
                    ->setParameter('{{ providedValue }}', '"' . $value . '"')
                    ->setParameter('{{ parameter }}', $this->context->getPropertyName())
                    ->setParameter('{{ allowedValues }}', join(' , ', $allowedValues))
                    ->addViolation();

                break;
            }
        }
    }
}
