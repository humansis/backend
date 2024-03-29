<?php

declare(strict_types=1);

namespace Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
class SelectionCriterionField extends Constraint
{
    final public const INVALID_FIELD_ERROR = 'd6aadef3-8dd1-4f11-900a-215ecd726cd1';
    final public const INVALID_CONDITION_ERROR = 'd6aadef3-8dd1-4f11-900a-215ecd726cd2';
    final public const INVALID_VALUE_ERROR = 'd6aadef3-8dd1-4f11-900a-215ecd726cd3';

    protected static $errorNames = [
        self::INVALID_FIELD_ERROR => 'INVALID_FIELD_ERROR',
        self::INVALID_CONDITION_ERROR => 'INVALID_CONDITION_ERROR',
        self::INVALID_VALUE_ERROR => 'INVALID_VALUE_ERROR',
    ];

    public $errorFieldMessage = 'Field {{ field }} is not supported for target {{ target }}.';

    public $errorConditionMessage = 'Condition {{ condition }} is not supported for field {{ field }}.';

    public $errorValueMessage = 'Invalid value \'{{ value }}\' for field {{ field }}.';

    /**
     * @inheritdoc
     */
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
