<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Assistance\Scoring\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
final class ScoringType extends Constraint
{
    public $message = 'Invalid scoring type \'{{ value }}\'.';
}