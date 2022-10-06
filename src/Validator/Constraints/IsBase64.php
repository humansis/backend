<?php

declare(strict_types=1);

namespace Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
class IsBase64 extends Constraint
{
    public $message = 'Value \'{{ value }}\' has to be base64.';

    public function __construct($options = null)
    {
        parent::__construct($options);
    }
}
