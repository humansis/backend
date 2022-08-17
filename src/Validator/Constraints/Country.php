<?php
declare(strict_types=1);

namespace Validator\Constraints;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
class Country extends \Symfony\Component\Validator\Constraints\Country
{
    public function __construct($options = null)
    {
        \Symfony\Component\Validator\Constraint::__construct($options);
    }
}
