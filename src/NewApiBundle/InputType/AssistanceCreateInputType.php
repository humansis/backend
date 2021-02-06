<?php

declare(strict_types=1);

namespace NewApiBundle\InputType;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Assert\GroupSequence({"AssistanceCreateInputType", "Strict"})
 */
class AssistanceCreateInputType extends AssistanceUpdateInputType
{

}
