<?php
declare(strict_types=1);

namespace Validator\Constraints;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
class ImportDate extends \Symfony\Component\Validator\Constraints\Date
{
}
