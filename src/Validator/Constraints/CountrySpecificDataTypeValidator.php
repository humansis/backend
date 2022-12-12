<?php

declare(strict_types=1);

namespace Validator\Constraints;

use Component\Import\Enum\ImportCsoEnum;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class CountrySpecificDataTypeValidator extends ConstraintValidator
{
    public function __construct(private ?\Symfony\Component\PropertyAccess\PropertyAccessorInterface $propertyAccessor = null)
    {
    }

    public function validate($object, Constraint $constraint)
    {
        if (!$constraint instanceof CountrySpecificDataType) {
            throw new UnexpectedTypeException($constraint, CountrySpecificDataType::class);
        }

        if (null === $object) {
            return;
        }

        if (!key_exists(ImportCsoEnum::ImportLineEntityKey->value, $object)) {
            throw new ConstraintDefinitionException(
                sprintf('Invalid property path "%s" provided to "%s" constraint: ', ImportCsoEnum::ImportLineEntityKey->value, get_debug_type($constraint)),
                0
            );
        }
        if (!key_exists(ImportCsoEnum::ImportLineValueKey->value, $object)) {
            throw new ConstraintDefinitionException(
                sprintf(
                    'Invalid property path "%s" provided to "%s" constraint: ',
                    ImportCsoEnum::ImportLineValueKey->value,
                    get_debug_type($constraint)
                ),
                0
            );
        }
        $countrySpecific = $object[ImportCsoEnum::ImportLineEntityKey->value];
        if (!$this->hasValueCorrectNumberType($countrySpecific, $object['value'])) {
            $violationBuilder = $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $object['value'])
                ->setParameter('{{ countrySpecific }}', $countrySpecific->getFieldString());

            $violationBuilder->addViolation();
        }
    }

    private function hasValueCorrectNumberType($countrySpecific, $value): bool
    {
        if (!isset($countrySpecific)) {
            return false;
        }
        if ($countrySpecific->getType() === 'number') {
            return is_numeric($value);
        }

        return true;
    }

    private function getPropertyAccessor(): PropertyAccessorInterface
    {
        if (null === $this->propertyAccessor) {
            $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        }

        return $this->propertyAccessor;
    }
}
