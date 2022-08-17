<?php
declare(strict_types=1);

namespace Validator\Constraints;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
class Iso8601 extends \Symfony\Component\Validator\Constraints\DateTime
{
}
