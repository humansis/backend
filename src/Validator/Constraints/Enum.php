<?php
declare(strict_types=1);

namespace Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
class Enum extends Constraint
{
    public $message = 'Provided value {{ providedValue }} is not allowed for parameter "{{ parameter }}". Allowed values are: [ {{ allowedValues }} ].';

    public $enumClass;
    public $includeAPIAlternatives = true;
    public $array = false;

    /**
     * @return string[]
     */
    public function getRequiredOptions(): array
    {
        return ['enumClass'];
    }
}
