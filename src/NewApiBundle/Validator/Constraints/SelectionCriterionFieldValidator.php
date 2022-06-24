<?php
declare(strict_types=1);

namespace NewApiBundle\Validator\Constraints;

use NewApiBundle\Component\SelectionCriteria\SelectionCriterionService;
use NewApiBundle\Component\SelectionCriteria\Structure\Field;
use NewApiBundle\Enum\SelectionCriteriaTarget;
use NewApiBundle\InputType\Assistance\SelectionCriterionInputType;
use NewApiBundle\InputType\AssistanceCreateInputType;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class SelectionCriterionFieldValidator extends ConstraintValidator
{
    /** @var SelectionCriterionService */
    private $service = [];

    public function __construct(SelectionCriterionService $service)
    {
        $this->service = $service;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof SelectionCriterionField) {
            throw new UnexpectedTypeException($constraint, SelectionCriterionField::class);
        }

        if (!$value instanceof SelectionCriterionInputType) {
            throw new \UnexpectedValueException(self::class.' supports only '.SelectionCriterionInputType::class);
        }

        /** @var AssistanceCreateInputType $root */
        $root = $this->context->getRoot();
        $countryIso3 = $root->getIso3();

        if (!is_string($value->getTarget()) || !in_array($value->getTarget(), SelectionCriteriaTarget::values())) {
            $this->context->addViolation("Target should be one of ".implode(',', SelectionCriteriaTarget::values()));

            return;
        }

        $fields = $this->service->findFieldsByTarget($value->getTarget(), $countryIso3);
        foreach ($fields as $field) {
            if ($field->getCode() === $value->getField()) {
                $this->validateCondition($constraint, $field, $value);
                $this->validateValue($constraint, $field, $value);

                return;
            }
        }

        $this->context->buildViolation($constraint->errorFieldMessage)
            ->setParameter('{{ target }}', $value->getTarget())
            ->setParameter('{{ field }}', $value->getField())
            ->setCode(SelectionCriterionField::INVALID_FIELD_ERROR)
            ->atPath('field')
            ->addViolation();
    }

    private function validateCondition(SelectionCriterionField $constraint, Field $field, SelectionCriterionInputType $value)
    {
        if (in_array($value->getCondition(), $field->getConditions(), true)) {
            return;
        }

        $this->context->buildViolation($constraint->errorConditionMessage)
            ->setParameter('{{ field }}', $value->getField())
            ->setParameter('{{ condition }}', $value->getCondition())
            ->setCode(SelectionCriterionField::INVALID_CONDITION_ERROR)
            ->atPath('condition')
            ->addViolation();
    }

    private function validateValue(SelectionCriterionField $constraint, Field $field, SelectionCriterionInputType $value)
    {
        if ($field->isValid($value->getValue())) {
            return;
        }

        $this->context->buildViolation($constraint->errorValueMessage)
            ->setParameter('{{ field }}', $value->getField())
            ->setParameter('{{ value }}', $value->getValue())
            ->setCode(SelectionCriterionField::INVALID_VALUE_ERROR)
            ->atPath('value')
            ->addViolation();
    }

}
