<?php

declare(strict_types=1);

namespace Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
class Enum extends Constraint
{
    public string $message = 'Provided value {{ providedValue }} is not allowed for parameter "{{ parameter }}". Allowed values are: [ {{ allowedValues }} ].';

    public string $enumClass;
    public bool $includeAPIAlternatives = true;

    public mixed $array = false;

    public function __construct(
        mixed $options = null,
        array $groups = null
    ) {
        parent::__construct($options, $groups);
    }

    /**
     * @return string[]
     */
    public function getRequiredOptions(): array
    {
        return ['enumClass'];
    }
}
