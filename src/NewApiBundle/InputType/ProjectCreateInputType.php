<?php

declare(strict_types=1);

namespace NewApiBundle\InputType;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Assert\GroupSequence({"ProjectCreateInputType", "Strict"})
 */
class ProjectCreateInputType extends ProjectUpdateInputType
{
}
