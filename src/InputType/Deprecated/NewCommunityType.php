<?php

declare(strict_types=1);

namespace InputType\Deprecated;

use Symfony\Component\Validator\Constraints as Assert;

class NewCommunityType extends UpdateCommunityType
{
    /**
     * @var int[]
     * @Assert\NotNull
     * @Assert\Count(min="1")
     */
    public $projects;
}
