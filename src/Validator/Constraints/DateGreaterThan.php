<?php

declare(strict_types=1);

namespace Validator\Constraints;

use Symfony\Component\Validator\Constraints\GreaterThan;

#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
class DateGreaterThan extends GreaterThan
{
    final public const TOO_LOW_ERROR = 'd6aadef3-8df1-4f11-900a-215ecd726cd6';

    protected static $errorNames = [
        self::TOO_LOW_ERROR => 'TOO_LOW_ERROR',
    ];

    public $message = 'This date should be greater than {{ compared_value }}.';
}
