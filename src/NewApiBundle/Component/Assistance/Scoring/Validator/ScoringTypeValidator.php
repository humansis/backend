<?php

namespace NewApiBundle\Component\Assistance\Scoring\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ScoringTypeValidator extends ConstraintValidator
{
    /**
     * @var array
     */
    private $scoringConfigurations;

    /**
     * @param array $scoringConfigurations
     */
    public function __construct(array $scoringConfigurations)
    {
        $this->scoringConfigurations = $scoringConfigurations;
    }

    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof ScoringType) {
            throw new UnexpectedTypeException($constraint, ScoringType::class);
        }
        
        if (null === $value || '' === $value) {
            return;
        }

        if (!in_array($value, array_column($this->scoringConfigurations, 'name'), true)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $value)
                ->addViolation();
        }
    }
}