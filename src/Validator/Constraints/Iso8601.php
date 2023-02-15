<?php

declare(strict_types=1);

namespace Validator\Constraints;

use Symfony\Component\Validator\Constraints\DateTime;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Iso8601 extends DateTime
{
}
