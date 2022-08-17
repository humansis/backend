<?php

declare(strict_types=1);

namespace InputType;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Assert\GroupSequence({"ProjectCreateInputType", "Strict"})
 */
class ProjectCreateInputType extends ProjectUpdateInputType
{
}
