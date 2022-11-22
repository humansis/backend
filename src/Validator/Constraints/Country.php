<?php

declare(strict_types=1);

namespace Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
class Country extends \Symfony\Component\Validator\Constraints\Country
{
    public function __construct($options = null)
    {
        Constraint::__construct($options);
    }
}
