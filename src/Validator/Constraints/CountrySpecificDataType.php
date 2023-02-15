<?php

declare(strict_types=1);

namespace Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
class CountrySpecificDataType extends Constraint
{
    public string $message = 'Value \'{{ value }}\' has to be number.';

    public function __construct($options = null)
    {
        parent::__construct($options);
    }
}
