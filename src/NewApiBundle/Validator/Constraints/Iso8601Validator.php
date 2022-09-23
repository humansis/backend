<?php
declare(strict_types=1);

namespace NewApiBundle\Validator\Constraints;

use NewApiBundle\Utils\DateTime\Iso8601Converter;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class Iso8601Validator extends \Symfony\Component\Validator\Constraints\DateTimeValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof Iso8601) {
            throw new UnexpectedTypeException($constraint, Iso8601::class);
        }

        if (null === $value || '' === $value || $value instanceof \DateTimeInterface) {
            return;
        }

        if (!is_scalar($value) && !(\is_object($value) && method_exists($value, '__toString'))) {
            throw new UnexpectedTypeException($value, 'string');
        }

        $value = (string) $value;

        Iso8601Converter::toDateTime($value);
        $errors = \DateTime::getLastErrors();

        if (0 < $errors['error_count']) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->setCode(Iso8601::INVALID_FORMAT_ERROR)
                ->addViolation();

            return;
        }

        foreach ($errors['warnings'] as $warning) {
            if ('The parsed date was invalid' === $warning) {
                $this->context->buildViolation($constraint->message)
                    ->setParameter('{{ value }}', $this->formatValue($value))
                    ->setCode(Iso8601::INVALID_DATE_ERROR)
                    ->addViolation();
            } elseif ('The parsed time was invalid' === $warning) {
                $this->context->buildViolation($constraint->message)
                    ->setParameter('{{ value }}', $this->formatValue($value))
                    ->setCode(Iso8601::INVALID_TIME_ERROR)
                    ->addViolation();
            } else {
                $this->context->buildViolation($constraint->message)
                    ->setParameter('{{ value }}', $this->formatValue($value))
                    ->setCode(Iso8601::INVALID_FORMAT_ERROR)
                    ->addViolation();
            }
        }
    }
}