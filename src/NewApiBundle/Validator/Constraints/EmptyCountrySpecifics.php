<?php
declare(strict_types=1);

namespace NewApiBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
class EmptyCountrySpecifics extends Constraint
{
    public $message = 'This value should be empty.';

    public function __construct($options = null)
    {
        parent::__construct($options);
    }
}
