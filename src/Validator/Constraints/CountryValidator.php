<?php

declare(strict_types=1);

namespace Validator\Constraints;

use Component\Country\Countries;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Country as SymfonyCountry;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

use function is_object;

class CountryValidator extends ConstraintValidator
{
    /** @var Countries */
    private $countries;

    public function __construct(Countries $countries)
    {
        $this->countries = $countries;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof Country) {
            throw new UnexpectedTypeException($constraint, Country::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!is_scalar($value) && !(is_object($value) && method_exists($value, '__toString'))) {
            throw new UnexpectedValueException($value, 'string');
        }

        $value = (string) $value;

        if (!$this->countries->getCountry($value)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->setCode(SymfonyCountry::NO_SUCH_COUNTRY_ERROR)
                ->addViolation();
        }
    }
}
