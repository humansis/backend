<?php

declare(strict_types=1);

namespace Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
class CountrySpecificDataType extends Constraint
{
    public $message = 'Value \'{{ value }}\' has to be number.';

    public function __construct($options = null)
    {
        parent::__construct($options);
    }
}
