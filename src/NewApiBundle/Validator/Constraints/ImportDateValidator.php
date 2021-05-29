<?php
declare(strict_types=1);

namespace NewApiBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Date;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * Class ImportDateValidator
 *
 * Very ugly hack because DateValidator written bad so it can't be extendable
 */
class ImportDateValidator extends \Symfony\Component\Validator\Constraints\DateValidator
{
    public const PATTERN = '/^(\d{2}).(\d{2}).(\d{4})$/';

    /**
     * @inheritDoc
     *
     * FYI: Changed order of parameters
     */
    public static function checkDate(int $day, int $month, int $year): bool
    {
        return checkdate($month, $day, $year);
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof Date) {
            throw new UnexpectedTypeException($constraint, Date::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if ($value instanceof \DateTimeInterface) {
            @trigger_error(sprintf('Validating a \\DateTimeInterface with "%s" is deprecated since version 4.2. Use "%s" instead or remove the constraint if the underlying model is already type hinted to \\DateTimeInterface.', Date::class, Type::class), \E_USER_DEPRECATED);

            return;
        }

        if (!is_scalar($value) && !(\is_object($value) && method_exists($value, '__toString'))) {
            throw new UnexpectedValueException($value, 'string');
        }

        $value = (string) $value;

        if (!preg_match($this::PATTERN, $value, $matches)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->setCode(Date::INVALID_FORMAT_ERROR)
                ->addViolation();

            return;
        }

        if (!$this::checkDate((int)$matches[1], (int)$matches[2], (int)$matches[3])) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->setCode(Date::INVALID_DATE_ERROR)
                ->addViolation();
        }
    }
}
