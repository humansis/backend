<?php

declare(strict_types=1);

namespace InputType;

use InputType\FilterFragment\FulltextFilterTrait;
use Request\FilterInputType\AbstractFilterInputType;
use Symfony\Component\Validator\Constraints as Assert;

class ProjectsAssistanceFilterInputType extends AbstractFilterInputType
{
    use FulltextFilterTrait;
}
