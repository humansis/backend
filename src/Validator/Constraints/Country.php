<?php

declare(strict_types=1);

namespace Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
class Country extends \Symfony\Component\Validator\Constraints\Country
{
    public function __construct(mixed $options = null, array $groups = null)
    {
        Constraint::__construct($options, $groups);
    }
}
