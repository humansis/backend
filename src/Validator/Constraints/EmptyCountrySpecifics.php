<?php

declare(strict_types=1);

namespace Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
class EmptyCountrySpecifics extends Constraint
{
    public string $message = 'This value should be empty.';

    public function __construct(mixed $options = null, array $groups = null)
    {
        parent::__construct($options, $groups);
    }
}
